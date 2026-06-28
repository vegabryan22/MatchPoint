<?php

namespace App\Policies;

use App\Models\GameMatch;
use App\Models\User;

final class GameMatchPolicy
{
    public function viewResult(User $user, GameMatch $match): bool
    {
        return $user->is_active;
    }

    public function recordResult(User $user, GameMatch $match): bool
    {
        return $user->can('manageMatches', $match->tournament);
    }
}
