<?php

namespace App\Repositories\Eloquent;

use App\Models\GameMatch;
use App\Repositories\Contracts\MatchResultRepositoryInterface;

final class EloquentMatchResultRepository implements MatchResultRepositoryInterface
{
    public function replaceScores(GameMatch $match, array $games, int $userId): void
    {
        $match->scores()->delete();

        foreach ($games as $game) {
            $match->scores()->create([
                ...$game,
                'created_by' => $userId,
            ]);
        }
    }
}
