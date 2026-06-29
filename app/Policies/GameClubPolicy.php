<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\GameClub;
use App\Models\User;

final class GameClubPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, GameClub $club): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, GameClub $club): bool
    {
        return $this->manage($user);
    }

    private function manage(User $user): bool
    {
        return $user->hasRole(RoleName::Administrator) || $user->hasRole(RoleName::Organizer);
    }
}
