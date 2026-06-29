<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Team;
use App\Models\User;
use App\Services\TournamentAccessService;

final class TeamPolicy
{
    public function __construct(private readonly TournamentAccessService $access) {}

    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Team $team): bool
    {
        return $user->is_active && ($user->isAdministrator() || $team->managed_by === $user->id
            || $team->tournaments()->whereKey($this->access->visibleQuery($user)->pluck('id'))->exists());
    }

    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdministrator() || $user->hasRole(RoleName::Organizer));
    }

    public function update(User $user, Team $team): bool
    {
        return $user->isAdministrator() || ($user->hasRole(RoleName::Organizer) && ($team->managed_by === $user->id
            || $team->tournaments()->whereHas('organizers', fn ($query) => $query->whereKey($user->id))->exists()));
    }

    public function delete(User $user, Team $team): bool
    {
        return $this->update($user, $team);
    }
}
