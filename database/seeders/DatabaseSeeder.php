<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\SubmissionAnswer;
use App\Models\Post;


class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $users = User::factory()->count(2)->create();

        Post::factory()->count(15)->create([
            'author_id' => fn() => collect([$admin, ...$users])->random()->id,
        ]);

        Form::factory()->count(4)->create()->each(function (Form $form) {
            $fields = collect([
                FormField::factory()->for($form)->create(['order' => 1]),
                FormField::factory()->email(2)->for($form)->create(),
                FormField::factory()->university(3)->for($form)->create(),
                FormField::factory()->attendedBefore(4)->for($form)->create(),
            ]);

            FormSubmission::factory()
                ->count(fake()->numberBetween(5, 25))
                ->for($form)
                ->create()
                ->each(function (FormSubmission $submission) use ($fields) {
                    foreach ($fields as $field) {
                        SubmissionAnswer::factory()->create([
                            'submission_id' => $submission->id,
                            'field_id' => $field->id,
                            'answer' => match ($field->type) {
                                'email' => fake()->safeEmail(),
                                'select', 'radio' => fake()->randomElement($field->options),
                                default => fake()->name(),
                            },
                        ]);
                    }
                });
        });
    }
}
