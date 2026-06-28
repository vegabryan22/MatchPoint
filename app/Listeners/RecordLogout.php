<?php

namespace App\Listeners;

use App\Services\AuditService;
use Illuminate\Auth\Events\Logout;

final class RecordLogout
{
    public function __construct(private readonly AuditService $audit) {}

    public function handle(Logout $event): void
    {
        if ($event->user !== null) {
            $this->audit->record('auth.logout', $event->user, userId: $event->user->getAuthIdentifier());
        }
    }
}
