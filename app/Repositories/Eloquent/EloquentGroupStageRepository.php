<?php

namespace App\Repositories\Eloquent;

use App\Enums\BracketType;
use App\Enums\MatchStatus;
use App\Models\GameMatch;
use App\Models\Round;
use App\Models\Tournament;
use App\Models\TournamentGroup;
use App\Repositories\Contracts\GroupStageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class EloquentGroupStageRepository implements GroupStageRepositoryInterface
{
    public function createGroup(array $attributes): TournamentGroup
    {
        return TournamentGroup::query()->create($attributes);
    }

    public function addParticipant(TournamentGroup $group, array $attributes): void
    {
        $group->participants()->create($attributes);
    }

    public function createRound(array $attributes): Round
    {
        return Round::query()->create($attributes);
    }

    public function createMatch(array $attributes): GameMatch
    {
        return GameMatch::query()->create($attributes);
    }

    public function clear(Tournament $tournament): void
    {
        $tournament->rounds()->where('bracket', BracketType::Group)->delete();
        $tournament->groups()->delete();
    }

    public function hasCompletedMatches(Tournament $tournament): bool
    {
        return $tournament->matches()
            ->whereNotNull('group_id')
            ->where('status', MatchStatus::Completed)
            ->exists();
    }

    public function hasKnockoutRounds(Tournament $tournament): bool
    {
        return $tournament->rounds()->where('bracket', BracketType::Main)->exists();
    }

    public function groups(Tournament $tournament): Collection
    {
        return $tournament->groups()
            ->with(['participants', 'matches.scores', 'matches.round', 'matches.group', 'matches.station'])
            ->get();
    }
}
