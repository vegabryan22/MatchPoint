<?php

namespace Database\Factories;

use App\Enums\BracketType;
use App\Models\Round;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Round> */
class RoundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'name' => 'Ronda 1',
            'number' => 1,
            'bracket' => BracketType::Main,
            'starts_at' => null,
        ];
    }
}
