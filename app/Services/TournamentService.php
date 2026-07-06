<?php

namespace App\Services;

use App\Enums\GameType;
use App\Enums\RoleName;
use App\Enums\TournamentStatus;
use App\Models\Tournament;
use App\Models\User;
use App\Repositories\Contracts\TournamentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class TournamentService
{
    /** @var array<string, list<TournamentStatus>> */
    private const TRANSITIONS = [
        TournamentStatus::Draft->value => [TournamentStatus::Registration, TournamentStatus::Cancelled],
        TournamentStatus::Registration->value => [TournamentStatus::Draft, TournamentStatus::InProgress, TournamentStatus::Cancelled],
        TournamentStatus::InProgress->value => [TournamentStatus::Finished, TournamentStatus::Cancelled],
        TournamentStatus::Finished->value => [],
        TournamentStatus::Cancelled->value => [TournamentStatus::Draft],
    ];

    public function __construct(
        private readonly TournamentRepositoryInterface $tournaments,
        private readonly AuditService $audit,
    ) {}

    public function paginate(array $filters, User $user): LengthAwarePaginator
    {
        return $this->tournaments->paginate($filters, $user);
    }

    public function create(array $data, User $creator): Tournament
    {
        $this->normalizeCustomGame($data);
        $this->normalizeQuickRegistration($data);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['status'] = TournamentStatus::Draft;
        $data['created_by'] = $creator->getKey();

        return DB::transaction(function () use ($data, $creator): Tournament {
            $tournament = $this->tournaments->create($data);
            $this->assignCreatorAsOrganizer($tournament, $creator);

            return $tournament;
        });
    }

    public function update(Tournament $tournament, array $data): Tournament
    {
        $this->ensureEditable($tournament);
        $this->normalizeCustomGame($data);
        $this->normalizeQuickRegistration($data);

        return DB::transaction(fn (): Tournament => $this->tournaments->update($tournament, $data));
    }

    public function duplicate(Tournament $tournament, User $creator): Tournament
    {
        $start = $tournament->starts_at->isFuture()
            ? $tournament->starts_at->copy()->addWeek()
            : now()->addWeek();
        $duration = $tournament->ends_at?->diffInSeconds($tournament->starts_at);
        $name = "Copia de {$tournament->name}";

        $copy = DB::transaction(function () use ($tournament, $creator, $start, $duration, $name): Tournament {
            return $this->tournaments->create([
                'created_by' => $creator->getKey(),
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'description' => $tournament->description,
                'game' => $tournament->game,
                'custom_game' => $tournament->custom_game,
                'participant_type' => $tournament->participant_type,
                'max_participants' => $tournament->max_participants,
                'format' => $tournament->format,
                'best_of' => $tournament->best_of,
                'status' => TournamentStatus::Draft,
                'registration_starts_at' => null,
                'registration_ends_at' => null,
                'starts_at' => $start,
                'ends_at' => $duration === null ? null : $start->copy()->addSeconds($duration),
                'quick_registration_enabled' => false,
                'quick_registration_levels' => $tournament->quick_registration_levels,
                'quick_registration_notice' => $tournament->quick_registration_notice,
            ]);
        });

        $this->audit->record('tournament.duplicated', $tournament, [], ['copy_id' => $copy->id]);
        $this->assignCreatorAsOrganizer($copy, $creator);

        return $copy;
    }

    public function transition(Tournament $tournament, TournamentStatus $target): Tournament
    {
        if (! in_array($target, $this->allowedTransitions($tournament), true)) {
            throw ValidationException::withMessages([
                'status' => "No se puede cambiar de {$tournament->status->label()} a {$target->label()}.",
            ]);
        }

        $previous = $tournament->status;
        $changes = ['status' => $target];

        if ($target === TournamentStatus::Finished && $tournament->ends_at === null) {
            $changes['ends_at'] = now();
        }

        if ($target === TournamentStatus::Finished) {
            $changes['extraordinary_registration_enabled'] = false;
        }

        $tournament = DB::transaction(fn (): Tournament => $this->tournaments->update($tournament, $changes));
        $this->audit->record('tournament.status_changed', $tournament, ['status' => $previous->value], ['status' => $target->value]);

        return $tournament;
    }

    /** @return list<TournamentStatus> */
    public function allowedTransitions(Tournament $tournament): array
    {
        return self::TRANSITIONS[$tournament->status->value];
    }

    public function delete(Tournament $tournament): void
    {
        if (! in_array($tournament->status, [TournamentStatus::Draft, TournamentStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'tournament' => 'Sólo se pueden eliminar torneos en borrador o cancelados.',
            ]);
        }

        DB::transaction(function () use ($tournament): void {
            $this->tournaments->delete($tournament);
        });
    }

    private function ensureEditable(Tournament $tournament): void
    {
        if (! in_array($tournament->status, [TournamentStatus::Draft, TournamentStatus::Registration], true)) {
            throw ValidationException::withMessages([
                'tournament' => 'La configuración sólo puede editarse en borrador o inscripciones.',
            ]);
        }
    }

    private function normalizeCustomGame(array &$data): void
    {
        if (($data['game'] ?? null) !== GameType::Other->value) {
            $data['custom_game'] = null;
        }
    }

    private function normalizeQuickRegistration(array &$data): void
    {
        if (array_key_exists('quick_registration_enabled', $data)) {
            $data['quick_registration_enabled'] = (bool) $data['quick_registration_enabled']
                && ($data['participant_type'] ?? null) === 'individual';
        }

        if (array_key_exists('quick_registration_levels', $data)) {
            $data['quick_registration_levels'] = collect($data['quick_registration_levels'])
                ->map(fn (string $level): string => trim($level))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'torneo';
        $slug = $base;
        $suffix = 2;

        while ($this->tournaments->slugExists($slug)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function assignCreatorAsOrganizer(Tournament $tournament, User $creator): void
    {
        if (! $creator->hasRole(RoleName::Organizer)) {
            return;
        }

        $tournament->organizers()->syncWithoutDetaching([$creator->id => [
            'assigned_by' => $creator->id,
            'is_primary' => true,
            'assigned_at' => now(),
        ]]);
    }
}
