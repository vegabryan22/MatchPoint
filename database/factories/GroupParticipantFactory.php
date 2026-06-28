<?php

namespace Database\Factories;

use App\Enums\ParticipantType;
use App\Models\GroupParticipant;
use App\Models\TournamentGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GroupParticipant> */
class GroupParticipantFactory extends Factory
{
    public function definition(): array
    {
        return ['group_id' => TournamentGroup::factory(), 'participant_type' => ParticipantType::Individual, 'participant_id' => 1, 'seed' => 1];
    }
}
