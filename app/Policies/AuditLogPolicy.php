<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

final class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->isAdministrator();
    }
}
