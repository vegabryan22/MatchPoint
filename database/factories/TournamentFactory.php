<?php

namespace Database\Factories;

use App\Enums\BestOf;
use App\Enums\GameType;
use App\Enums\ParticipantType;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Tournament> */
class TournamentFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'created_by' => User::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'description' => fake()->sentence(),
            'game' => GameType::EaSportsFc,
            'custom_game' => null,
            'participant_type' => ParticipantType::Individual,
            'max_participants' => 16,
            'format' => TournamentFormat::SingleElimination,
            'best_of' => BestOf::One,
            'status' => TournamentStatus::Draft,
            'registration_starts_at' => now()->addDay(),
            'registration_ends_at' => now()->addWeek(),
            'starts_at' => now()->addWeeks(2),
            'ends_at' => now()->addWeeks(2)->addDay(),
        ];
    }

    public function status(TournamentStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }
}
