<?php

namespace App\Repositories\Eloquent;

use App\Enums\MatchStatus;
use App\Models\GameMatch;
use App\Models\Tournament;
use App\Models\TournamentStation;
use App\Repositories\Contracts\TournamentScheduleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class EloquentTournamentScheduleRepository implements TournamentScheduleRepositoryInterface
{
    public function stations(Tournament $tournament): Collection
    {
        return $tournament->stations()->withCount('matches')->get();
    }

    public function schedulableMatches(Tournament $tournament): Collection
    {
        return $tournament->matches()
            ->with(['round', 'group'])
            ->where('status', MatchStatus::Pending)
            ->where('is_conditional', false)
            ->orderBy('round_id')
            ->orderBy('sequence')
            ->get();
    }

    public function playableMatches(Tournament $tournament): Collection
    {
        return $tournament->matches()
            ->with('round')
            ->whereNotIn('status', [MatchStatus::Bye, MatchStatus::Cancelled])
            ->where('is_conditional', false)
            ->orderBy('round_id')
            ->orderBy('sequence')
            ->get();
    }

    public function scheduledMatches(Tournament $tournament): Collection
    {
        return $tournament->matches()
            ->with(['round', 'group', 'station', 'scores'])
            ->whereNotNull('scheduled_at')
            ->orderBy('scheduled_at')
            ->orderBy('tournament_station_id')
            ->get();
    }

    public function createStation(Tournament $tournament, array $attributes): TournamentStation
    {
        return $tournament->stations()->create($attributes);
    }

    public function updateStation(TournamentStation $station, array $attributes): TournamentStation
    {
        $station->update($attributes);

        return $station->refresh();
    }

    public function deleteStation(TournamentStation $station): void
    {
        $station->delete();
    }

    public function updateSettings(Tournament $tournament, array $attributes): Tournament
    {
        $tournament->update($attributes);

        return $tournament->refresh();
    }

    public function scheduleMatch(GameMatch $match, array $attributes): void
    {
        $match->update($attributes);
    }

    public function clearPendingSchedule(Tournament $tournament): void
    {
        $tournament->matches()
            ->where('status', MatchStatus::Pending)
            ->update([
                'scheduled_at' => null,
                'scheduled_end_at' => null,
                'tournament_station_id' => null,
            ]);
    }
}
