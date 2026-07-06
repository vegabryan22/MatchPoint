<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Enums\RegistrationSource;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TournamentTeam> */
class TournamentTeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'team_id' => Team::factory(),
            'registered_by' => User::factory(),
            'source' => RegistrationSource::Manual,
            'seed' => null,
            'registered_at' => now(),
            'attendance_status' => AttendanceStatus::Pending,
        ];
    }
}
