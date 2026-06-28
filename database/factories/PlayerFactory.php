<?php

namespace Database\Factories;

use App\Enums\ControllerType;
use App\Enums\PlayerLevel;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Player> */
class PlayerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'nickname' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'photo_path' => null,
            'country' => fake()->country(),
            'preferred_controller' => fake()->randomElement(ControllerType::cases()),
            'level' => fake()->randomElement(PlayerLevel::cases()),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
