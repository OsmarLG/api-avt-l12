<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Person>
 */
class PersonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombres' => $this->faker->firstName,
            'apellido_paterno' => $this->faker->lastName,
            'apellido_materno' => $this->faker->lastName,
            'sexo' => $this->faker->randomElement(['masculino', 'femenino']),
            'fecha_nacimiento' => $this->faker->date(),
            'edad' => $this->faker->numberBetween(18, 99),
            'nacionalidad' => $this->faker->randomElement(['mexicana', 'estadounidense']),
            'estado_civil' => $this->faker->randomElement(['soltero', 'casado', 'divorciado', 'viudo', 'union_libre']),
            'curp' => $this->faker->unique()->regexify('[A-Z]{4}[0-9]{6}[H,M][A-Z]{5}[0-9]{2}'),
            'rfc' => $this->faker->unique()->regexify('[A-Z]{4}[0-9]{6}[A-Z,0-9]{3}'),
            'ine' => $this->faker->unique()->numerify('##################'),
            'ocupacion_profesion' => $this->faker->jobTitle,
            'pais_nacimiento' => $this->faker->country,
            'estado_nacimiento' => $this->faker->state,
            'municipio_nacimiento' => $this->faker->city,
            'localidad_nacimiento' => $this->faker->city,
            'calle' => $this->faker->streetName,
            'numero_interior' => $this->faker->buildingNumber,
            'numero_exterior' => $this->faker->buildingNumber,
            'colonia' => $this->faker->streetSuffix,
            'codigo_postal' => $this->faker->postcode,
            'pais_domicilio' => $this->faker->country,
            'estado_domicilio' => $this->faker->state,
            'municipio_domicilio' => $this->faker->city,
            'localidad_domicilio' => $this->faker->city,
        ];
    }
}
