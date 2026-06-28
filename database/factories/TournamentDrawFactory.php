<?php

namespace Database\Factories;

use App\Enums\DrawMethod;
use App\Models\Tournament;
use App\Models\TournamentDraw;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TournamentDraw> */
class TournamentDrawFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'generated_by' => User::factory(),
            'method' => DrawMethod::Random,
            'avoid_rematches' => false,
            'version' => 1,
            'metadata' => [],
            'generated_at' => now(),
        ];
    }
}
