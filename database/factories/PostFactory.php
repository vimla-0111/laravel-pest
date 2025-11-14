<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
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
        return [
            'title' => fake()->title(),
            'content' => fake()->paragraph(),
            'published_at' => now(),
            'created_by' => User::factory(),
            'status' => true
        ];
    }

    // public function createdBy($userId): static
    // {
    //     return $this->state(fn(array $attributes) => [
    //         'created_by' => $userId,
    //     ]);
    // }
}
