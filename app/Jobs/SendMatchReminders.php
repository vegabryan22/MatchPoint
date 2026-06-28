<?php

namespace App\Jobs;

use App\Services\MatchReminderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SendMatchReminders implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function handle(MatchReminderService $service): void
    {
        $service->sendDue();
    }
}
