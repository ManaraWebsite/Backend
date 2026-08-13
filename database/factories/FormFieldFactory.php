<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\FormField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormField>
 */
class FormFieldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'label' => 'Full name',
            'type' => 'text',
            'options' => null,
            'is_required' => true,
            'order' => 1,
        ];
    }

    public function email(int $order = 2): static
    {
        return $this->state(fn() => [
            'label' => 'Email',
            'type' => 'email',
            'options' => null,
            'is_required' => true,
            'order' => $order,
        ]);
    }

    public function university(int $order = 3): static
    {
        return $this->state(fn() => [
            'label' => 'University',
            'type' => 'select',
            'options' => ['UCAS', 'Islamic University of Gaza', 'Palestine University', 'Other'],
            'is_required' => true,
            'order' => $order,
        ]);
    }

    public function attendedBefore(int $order = 4): static
    {
        return $this->state(fn() => [
            'label' => 'Have you attended before?',
            'type' => 'radio',
            'options' => ['Yes', 'No'],
            'is_required' => false,
            'order' => $order,
        ]);
    }
}
