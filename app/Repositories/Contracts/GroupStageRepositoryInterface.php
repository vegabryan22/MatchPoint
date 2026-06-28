<?php

namespace App\Repositories\Contracts;

use App\Models\GameMatch;
use App\Models\Round;
use App\Models\Tournament;
use App\Models\TournamentGroup;
use Illuminate\Database\Eloquent\Collection;

interface GroupStageRepositoryInterface
{
    public function createGroup(array $attributes): TournamentGroup;

    public function addParticipant(TournamentGroup $group, array $attributes): void;

    public function createRound(array $attributes): Round;

    public function createMatch(array $attributes): GameMatch;

    public function clear(Tournament $tournament): void;

    public function hasCompletedMatches(Tournament $tournament): bool;

    public function hasKnockoutRounds(Tournament $tournament): bool;

    /** @return Collection<int, TournamentGroup> */
    public function groups(Tournament $tournament): Collection;
}
