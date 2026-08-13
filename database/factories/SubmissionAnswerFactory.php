<?php

namespace Database\Factories;

use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\SubmissionAnswer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionAnswer>
 */
class SubmissionAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submission_id' => FormSubmission::factory(),
            'field_id' => FormField::factory(),
            'answer' => fake()->word(),
        ];
    }
}
