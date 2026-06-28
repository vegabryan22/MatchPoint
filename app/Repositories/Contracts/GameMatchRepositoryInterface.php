<?php

namespace App\Repositories\Contracts;

use App\Models\GameMatch;
use Illuminate\Database\Eloquent\Collection;

interface GameMatchRepositoryInterface
{
    public function findForUpdate(int $matchId): GameMatch;

    /** @return Collection<int, GameMatch> */
    public function feedersFor(GameMatch $match): Collection;

    public function update(GameMatch $match, array $attributes): GameMatch;
}
