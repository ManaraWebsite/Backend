<?php

namespace Database\Factories;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Model>
 */
class FieldVoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'role' => fake()->randomElement([
                'Volunteer',
                'Journalist',
                'Student',
                'Community Member',
            ]),
            'quote' => fake()->paragraph(),
            'image' => null,
            'is_published' => fake()->boolean(80),
        ];
    }
}
