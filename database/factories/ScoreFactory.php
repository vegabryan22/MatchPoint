<?php

namespace Database\Factories;

use App\Models\GameMatch;
use App\Models\Score;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Score> */
class ScoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'match_id' => GameMatch::factory(),
            'game_number' => 1,
            'participant_a_score' => 2,
            'participant_b_score' => 1,
            'winner_id' => 1,
            'created_by' => User::factory(),
        ];
    }
}
