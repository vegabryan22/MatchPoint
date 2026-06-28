<?php

namespace Database\Factories;

use App\Models\Tournament;
use App\Models\TournamentGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TournamentGroup> */
class TournamentGroupFactory extends Factory
{
    public function definition(): array
    {
        return ['tournament_id' => Tournament::factory(), 'name' => 'Grupo A', 'position' => 1, 'qualifiers_count' => 2];
    }
}
