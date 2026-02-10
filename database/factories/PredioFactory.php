<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Predio>
 */
class PredioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clave_catastral' => $this->faker->unique()->numerify('###########'),
            'propietario' => $this->faker->name(),
            'ubicacion' => $this->faker->address(),
            'sup_cons' => $this->faker->randomFloat(2, 50, 500),
            'sup_terr' => $this->faker->randomFloat(2, 100, 1000),
            // simple triangle polygon
            'polygon' => new \MatanYadaev\EloquentSpatial\Objects\Polygon([
                new \MatanYadaev\EloquentSpatial\Objects\LineString([
                    new \MatanYadaev\EloquentSpatial\Objects\Point(0, 0),
                    new \MatanYadaev\EloquentSpatial\Objects\Point(0, 1),
                    new \MatanYadaev\EloquentSpatial\Objects\Point(1, 1),
                    new \MatanYadaev\EloquentSpatial\Objects\Point(0, 0),
                ])
            ]),
        ];
    }
}
