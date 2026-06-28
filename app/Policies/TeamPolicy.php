<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Team;
use App\Models\User;

final class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Team $team): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $this->managesTeams($user);
    }

    public function update(User $user, Team $team): bool
    {
        return $this->managesTeams($user);
    }

    public function delete(User $user, Team $team): bool
    {
        return $this->managesTeams($user);
    }

    private function managesTeams(User $user): bool
    {
        return $user->hasRole(RoleName::Administrator)
            || $user->hasRole(RoleName::Organizer);
    }
}
