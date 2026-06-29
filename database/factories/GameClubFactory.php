<?php

namespace Database\Factories;

use App\Enums\GameClubType;
use App\Enums\GameType;
use App\Models\GameClub;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GameClub> */
class GameClubFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'team_type' => GameClubType::Club,
            'country_code' => null,
            'crest_path' => null,
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (GameClub $club): void {
            if (! $club->availabilities()->exists()) {
                $club->availabilities()->create(['game' => GameType::EaSportsFc]);
            }
        });
    }
}
