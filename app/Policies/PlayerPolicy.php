<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Player;
use App\Models\User;

final class PlayerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Player $player): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $this->managesPlayers($user);
    }

    public function update(User $user, Player $player): bool
    {
        return $this->managesPlayers($user);
    }

    public function delete(User $user, Player $player): bool
    {
        return $this->managesPlayers($user);
    }

    private function managesPlayers(User $user): bool
    {
        return $user->hasRole(RoleName::Administrator)
            || $user->hasRole(RoleName::Organizer);
    }
}
