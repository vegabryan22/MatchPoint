<?php

namespace App\Repositories\Eloquent;

use App\Models\GameMatch;
use App\Repositories\Contracts\GameMatchRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class EloquentGameMatchRepository implements GameMatchRepositoryInterface
{
    public function findForUpdate(int $matchId): GameMatch
    {
        return GameMatch::query()->lockForUpdate()->findOrFail($matchId);
    }

    public function feedersFor(GameMatch $match): Collection
    {
        return GameMatch::query()
            ->where('winner_next_match_id', $match->id)
            ->orWhere('loser_next_match_id', $match->id)
            ->get();
    }

    public function update(GameMatch $match, array $attributes): GameMatch
    {
        $match->update($attributes);

        return $match->refresh();
    }
}
