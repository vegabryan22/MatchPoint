<?php

namespace App\Listeners;

use App\Events\MatchCompleted;
use App\Services\MatchAdvancementService;

final class AdvanceCompletedMatch
{
    public function __construct(private readonly MatchAdvancementService $advancement) {}

    public function handle(MatchCompleted $event): void
    {
        $this->advancement->advance($event->matchId, $event->actorId);
    }
}
