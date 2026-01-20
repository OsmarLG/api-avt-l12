<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\File>
 */
class FileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'user_id' => \App\Models\User::factory(),
            'title' => $this->faker->sentence(),
            'original_name' => $this->faker->word() . '.pdf',
            'disk' => 'public',
            'path' => 'files/' . $this->faker->uuid() . '.pdf',
            'mime_type' => 'application/pdf',
            'size' => $this->faker->numberBetween(1000, 5000),
            'visibility' => 'private',
        ];
    }
}
