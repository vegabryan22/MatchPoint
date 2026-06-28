<?php

namespace App\Listeners;

use App\Events\MatchCompleted;
use App\Services\TournamentChampionService;

final class UpdateTournamentChampion
{
    public function __construct(private readonly TournamentChampionService $champions) {}

    public function handle(MatchCompleted $event): void
    {
        $this->champions->sync($event->matchId, $event->actorId);
    }
}
