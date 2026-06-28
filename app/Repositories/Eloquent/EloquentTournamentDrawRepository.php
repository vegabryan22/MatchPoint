<?php

namespace App\Repositories\Eloquent;

use App\Enums\MatchStatus;
use App\Enums\ParticipantType;
use App\Models\GameMatch;
use App\Models\Round;
use App\Models\Tournament;
use App\Models\TournamentDraw;
use App\Repositories\Contracts\TournamentDrawRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class EloquentTournamentDrawRepository implements TournamentDrawRepositoryInterface
{
    public function createDraw(array $attributes): TournamentDraw
    {
        return TournamentDraw::query()->create($attributes);
    }

    public function createRound(array $attributes): Round
    {
        return Round::query()->create($attributes);
    }

    public function createMatch(array $attributes): GameMatch
    {
        return GameMatch::query()->create($attributes);
    }

    public function updateMatch(GameMatch $match, array $attributes): GameMatch
    {
        $match->update($attributes);

        return $match->refresh();
    }

    public function deleteArtifacts(Tournament $tournament): void
    {
        $tournament->rounds()->delete();
        $tournament->draw()->delete();
    }

    public function hasCompletedMatches(Tournament $tournament): bool
    {
        return $tournament->matches()->where('status', MatchStatus::Completed)->exists();
    }

    public function encounterCounts(Tournament $tournament, ParticipantType $type, array $participantIds): array
    {
        $counts = [];
        $matches = GameMatch::query()
            ->where('tournament_id', '!=', $tournament->id)
            ->where('participant_type', $type)
            ->where('status', MatchStatus::Completed)
            ->whereIn('participant_a_id', $participantIds)
            ->whereIn('participant_b_id', $participantIds)
            ->get(['participant_a_id', 'participant_b_id']);

        foreach ($matches as $match) {
            $key = $this->pairKey($match->participant_a_id, $match->participant_b_id);
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        return $counts;
    }

    public function updateSeeds(Tournament $tournament, array $orderedParticipantIds): void
    {
        $table = $tournament->participant_type === ParticipantType::Individual
            ? 'tournament_players'
            : 'tournament_teams';
        $column = $tournament->participant_type === ParticipantType::Individual ? 'player_id' : 'team_id';

        foreach ($orderedParticipantIds as $index => $participantId) {
            DB::table($table)
                ->where('tournament_id', $tournament->id)
                ->where($column, $participantId)
                ->update(['seed' => $index + 1, 'updated_at' => now()]);
        }
    }

    public function clearSeeds(Tournament $tournament): void
    {
        $table = $tournament->participant_type === ParticipantType::Individual
            ? 'tournament_players'
            : 'tournament_teams';

        DB::table($table)->where('tournament_id', $tournament->id)->update(['seed' => null, 'updated_at' => now()]);
    }

    private function pairKey(int $first, int $second): string
    {
        return min($first, $second).':'.max($first, $second);
    }
}
