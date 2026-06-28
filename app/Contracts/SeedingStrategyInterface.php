<?php

namespace App\Contracts;

use App\Enums\DrawMethod;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Collection;

interface SeedingStrategyInterface
{
    public function method(): DrawMethod;

    /** @return list<int> */
    public function order(Tournament $tournament, Collection $participants, array $data): array;
}
