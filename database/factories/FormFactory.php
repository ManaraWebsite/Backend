<?php

namespace Database\Factories;

use App\Models\Form;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Form>
 */
class FormFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->randomElement([
            'Backend Development Workshop',
            'Flutter Bootcamp',
            'Intro to System Design',
            'Clean Code Workshop',
            'Git & GitHub Fundamentals',
        ]).' #'.fake()->numberBetween(1, 5);

        return [
            'title_ar' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 9999),
            'description_ar' => fake()->paragraph(),
            'is_active' => fake()->boolean(80),
        ];
    }
}
