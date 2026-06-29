<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Enums\TournamentOfficialRole;
use App\Models\Tournament;
use App\Models\TournamentOfficial;
use App\Models\TournamentOrganizer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TournamentStaffService
{
    public function __construct(private readonly AuditService $audit) {}

    public function details(Tournament $tournament, User $actor): array
    {
        $tournament->load(['organizers.roles', 'officials.roles']);

        return [
            'tournament' => $tournament,
            'organizerCandidates' => $actor->isAdministrator() ? $this->usersWithRole(RoleName::Organizer) : collect(),
            'refereeCandidates' => $this->usersWithRole(RoleName::Referee),
        ];
    }

    public function assignOrganizer(Tournament $tournament, User $organizer, User $actor, bool $primary): void
    {
        $this->ensureRole($organizer, RoleName::Organizer);

        DB::transaction(function () use ($tournament, $organizer, $actor, $primary): void {
            $makePrimary = $primary || ! $tournament->organizers()->exists();
            if ($makePrimary) {
                TournamentOrganizer::query()->where('tournament_id', $tournament->id)->update(['is_primary' => false]);
            }
            TournamentOrganizer::query()->updateOrCreate(
                ['tournament_id' => $tournament->id, 'user_id' => $organizer->id],
                ['assigned_by' => $actor->id, 'is_primary' => $makePrimary, 'assigned_at' => now()],
            );
            $this->audit->record('tournament.organizer_assigned', $tournament, [], ['user_id' => $organizer->id, 'is_primary' => $makePrimary], $actor->id);
        });
    }

    public function removeOrganizer(Tournament $tournament, User $organizer, User $actor): void
    {
        DB::transaction(function () use ($tournament, $organizer, $actor): void {
            $assignment = TournamentOrganizer::query()->where('tournament_id', $tournament->id)->where('user_id', $organizer->id)->firstOrFail();
            $wasPrimary = $assignment->is_primary;
            $assignment->delete();
            if ($wasPrimary) {
                TournamentOrganizer::query()->where('tournament_id', $tournament->id)->oldest('id')->first()?->update(['is_primary' => true]);
            }
            $this->audit->record('tournament.organizer_removed', $tournament, ['user_id' => $organizer->id], [], $actor->id);
        });
    }

    public function assignReferee(Tournament $tournament, User $referee, User $actor): void
    {
        $this->ensureRole($referee, RoleName::Referee);
        TournamentOfficial::query()->updateOrCreate(
            ['tournament_id' => $tournament->id, 'user_id' => $referee->id, 'role' => TournamentOfficialRole::Referee],
            ['assigned_by' => $actor->id, 'is_active' => true, 'assigned_at' => now()],
        );
        $this->audit->record('tournament.referee_assigned', $tournament, [], ['user_id' => $referee->id], $actor->id);
    }

    public function removeReferee(Tournament $tournament, User $referee, User $actor): void
    {
        TournamentOfficial::query()->where('tournament_id', $tournament->id)
            ->where('user_id', $referee->id)->where('role', TournamentOfficialRole::Referee)->delete();
        $this->audit->record('tournament.referee_removed', $tournament, ['user_id' => $referee->id], [], $actor->id);
    }

    private function usersWithRole(RoleName $role)
    {
        return User::query()->where('is_active', true)->whereHas('roles', fn ($query) => $query->where('slug', $role->value))->orderBy('name')->get();
    }

    private function ensureRole(User $user, RoleName $role): void
    {
        if (! $user->is_active || ! $user->hasRole($role)) {
            throw ValidationException::withMessages(['user_id' => "El usuario debe estar activo y tener rol {$role->label()}."]);
        }
    }
}
