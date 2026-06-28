<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;

final class ReportPolicy
{
    public function export(User $user): bool
    {
        return $user->hasRole(RoleName::Administrator) || $user->hasRole(RoleName::Organizer);
    }
}
