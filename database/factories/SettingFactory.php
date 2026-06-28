<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Setting> */
class SettingFactory extends Factory
{
    public function definition(): array
    {
        $label = fake()->unique()->words(2, true);

        return [
            'key' => Str::snake($label),
            'value' => fake()->word(),
            'type' => 'string',
            'group' => 'general',
            'label' => ucfirst($label),
            'description' => fake()->sentence(),
            'is_public' => false,
        ];
    }
}
