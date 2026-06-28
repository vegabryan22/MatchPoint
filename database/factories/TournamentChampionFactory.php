<?php

namespace Database\Factories;

use App\Enums\ParticipantType;
use App\Models\GameMatch;
use App\Models\Tournament;
use App\Models\TournamentChampion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TournamentChampion> */
class TournamentChampionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'participant_type' => ParticipantType::Individual,
            'participant_id' => 1,
            'deciding_match_id' => GameMatch::factory(),
            'crowned_at' => now(),
        ];
    }
}
