<?php

namespace App\Repositories\Contracts;

use App\Models\GameMatch;

interface MatchResultRepositoryInterface
{
    public function replaceScores(GameMatch $match, array $games, int $userId): void;
}
