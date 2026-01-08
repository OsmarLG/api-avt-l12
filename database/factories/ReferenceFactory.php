<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reference>
 */
class ReferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombres' => $this->faker->name,
            'celular' => $this->faker->phoneNumber,
            'parentesco' => $this->faker->randomElement(['padre', 'madre', 'hermano', 'amigo', 'colega']),
        ];
    }
}
