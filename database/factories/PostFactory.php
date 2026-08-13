<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(6);
        $status = fake()->randomElement(['draft', 'published']);

        return [
            'author_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . fake()->unique()->numberBetween(1, 9999),
            'content' => implode("\n\n", fake()->paragraphs(6)),
            'cover_image' => 'https://picsum.photos/seed/' . fake()->uuid() . '/800/400',
            'status' => $status,
            'published_at' => $status === 'published' ? fake()->dateTimeBetween('-6 months', 'now') : null,
        ];
    }
}
