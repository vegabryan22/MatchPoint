<?php

namespace Database\Factories;

use App\Enums\BestOf;
use App\Enums\MatchStatus;
use App\Enums\ParticipantType;
use App\Models\GameMatch;
use App\Models\Round;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GameMatch> */
class GameMatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'round_id' => Round::factory(),
            'group_id' => null,
            'sequence' => 1,
            'participant_type' => ParticipantType::Individual,
            'participant_a_id' => null,
            'participant_b_id' => null,
            'winner_id' => null,
            'winner_next_match_id' => null,
            'winner_next_slot' => null,
            'loser_next_match_id' => null,
            'loser_next_slot' => null,
            'is_conditional' => false,
            'status' => MatchStatus::Pending,
            'best_of' => BestOf::One,
            'scheduled_at' => null,
            'tournament_station_id' => null,
            'scheduled_end_at' => null,
            'duration_seconds' => null,
            'observations' => null,
            'completed_by' => null,
            'completed_at' => null,
        ];
    }
}
