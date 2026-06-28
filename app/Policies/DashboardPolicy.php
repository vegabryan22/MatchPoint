<?php

namespace App\Policies;

use App\Models\User;

final class DashboardPolicy
{
    public function view(User $user): bool
    {
        return $user->is_active;
    }
}
