<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Enums\RegistrationSource;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\TournamentPlayer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TournamentPlayer> */
class TournamentPlayerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'player_id' => Player::factory(),
            'registered_by' => User::factory(),
            'source' => RegistrationSource::Manual,
            'seed' => null,
            'registered_at' => now(),
            'attendance_status' => AttendanceStatus::Pending,
        ];
    }
}
