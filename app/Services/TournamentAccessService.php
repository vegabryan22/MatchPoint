<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Enums\TournamentOfficialRole;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class TournamentAccessService
{
    public function visibleQuery(User $user): Builder
    {
        return $this->applyVisibility(Tournament::query(), $user);
    }

    public function applyVisibility(Builder $query, User $user): Builder
    {
        if ($user->isAdministrator()) {
            return $query;
        }

        return $query->where(function (Builder $visibility) use ($user): void {
            $visibility
                ->whereHas('organizers', fn (Builder $query) => $query->whereKey($user->id))
                ->orWhereHas('officials', fn (Builder $query) => $query
                    ->whereKey($user->id)
                    ->where('tournament_officials.is_active', true))
                ->orWhereHas('players', fn (Builder $query) => $query->where('players.user_id', $user->id));
        });
    }

    public function canView(User $user, Tournament $tournament): bool
    {
        return $user->is_active && $this->applyVisibility(Tournament::query()->whereKey($tournament->id), $user)->exists();
    }

    public function canManage(User $user, Tournament $tournament): bool
    {
        return $user->is_active && ($user->isAdministrator() || $tournament->organizers()->whereKey($user->id)->exists());
    }

    public function canManageOrganizers(User $user): bool
    {
        return $user->is_active && $user->isAdministrator();
    }

    public function canManageOfficials(User $user, Tournament $tournament): bool
    {
        return $this->canManage($user, $tournament);
    }

    public function canRecordMatches(User $user, Tournament $tournament): bool
    {
        return $this->canManage($user, $tournament)
            || ($user->hasRole(RoleName::Referee) && $tournament->officials()
                ->whereKey($user->id)
                ->wherePivot('role', TournamentOfficialRole::Referee->value)
                ->wherePivot('is_active', true)
                ->exists());
    }
}
