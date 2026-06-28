<?php

namespace App\Policies;

use App\Models\User;

final class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function updateAny(User $user): bool
    {
        return $user->isAdministrator();
    }
}
