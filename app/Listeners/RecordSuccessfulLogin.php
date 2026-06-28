<?php

namespace App\Listeners;

use App\Services\AuditService;
use Illuminate\Auth\Events\Login;

final class RecordSuccessfulLogin
{
    public function __construct(private readonly AuditService $audit) {}

    public function handle(Login $event): void
    {
        $this->audit->record('auth.login', $event->user, userId: $event->user->getAuthIdentifier());
    }
}
