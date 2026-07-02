<?php

namespace App\Repositories\Contracts;

use App\Models\GameMatch;
use App\Models\Tournament;
use App\Models\TournamentStation;
use Illuminate\Database\Eloquent\Collection;

interface TournamentScheduleRepositoryInterface
{
    /** @return Collection<int, TournamentStation> */
    public function stations(Tournament $tournament): Collection;

    /** @return Collection<int, GameMatch> */
    public function schedulableMatches(Tournament $tournament): Collection;

    /** @return Collection<int, GameMatch> */
    public function playableMatches(Tournament $tournament): Collection;

    /** @return Collection<int, GameMatch> */
    public function scheduledMatches(Tournament $tournament): Collection;

    public function createStation(Tournament $tournament, array $attributes): TournamentStation;

    public function updateStation(TournamentStation $station, array $attributes): TournamentStation;

    public function deleteStation(TournamentStation $station): void;

    public function updateSettings(Tournament $tournament, array $attributes): Tournament;

    public function scheduleMatch(GameMatch $match, array $attributes): void;

    public function clearPendingSchedule(Tournament $tournament): void;
}
