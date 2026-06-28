<?php

namespace App\Services\Draw;

use App\Contracts\SeedingStrategyInterface;
use App\Enums\DrawMethod;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Collection;

final class RandomSeedingStrategy implements SeedingStrategyInterface
{
    public function method(): DrawMethod
    {
        return DrawMethod::Random;
    }

    public function order(Tournament $tournament, Collection $participants, array $data): array
    {
        return $participants->shuffle()->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }
}
