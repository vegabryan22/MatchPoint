<?php

namespace Database\Factories;

use App\Enums\GamingPlatform;
use App\Models\Tournament;
use App\Models\TournamentStation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TournamentStation> */
class TournamentStationFactory extends Factory
{
    protected $model = TournamentStation::class;

    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'name' => 'Consola '.$this->faker->unique()->numberBetween(1, 99),
            'platform' => GamingPlatform::PlayStation5,
            'location' => $this->faker->optional()->word(),
            'is_active' => true,
            'available_from' => null,
            'available_until' => null,
        ];
    }
}
