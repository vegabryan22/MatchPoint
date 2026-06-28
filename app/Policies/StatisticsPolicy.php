<?php

namespace App\Policies;

use App\Models\User;

final class StatisticsPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }
}
