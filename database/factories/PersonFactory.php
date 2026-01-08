<?php

namespace Database\Factories;

use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonFactory extends Factory
{
    protected $model = Person::class;

    public function definition(): array
    {
        $sexo = $this->faker->randomElement(['masculino', 'femenino']);
        $gender = $sexo === 'masculino' ? 'male' : 'female';

        return [
            // ✅ usar métodos, no propiedades
            'nombres' => $this->faker->firstName($gender),
            'apellido_paterno' => $this->faker->lastName(),
            'apellido_materno' => $this->faker->optional()->lastName(),

            'sexo' => $sexo,
            'fecha_nacimiento' => $this->faker->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'edad' => $this->faker->numberBetween(18, 70),

            'nacionalidad' => $this->faker->randomElement(['mexicana', 'estadounidense']),
            'estado_civil' => $this->faker->randomElement(['soltero', 'casado', 'divorciado', 'viudo', 'union_libre']),

            // ✅ corrige comas en regex: [HM] y [A-Z0-9]
            'curp' => $this->faker->optional()->unique()->regexify('[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[0-9]{2}'),
            'rfc' => $this->faker->optional()->unique()->regexify('[A-Z]{4}[0-9]{6}[A-Z0-9]{3}'),

            // ✅ INE: mejor 13 (o el que quieras), pero consistente
            'ine' => $this->faker->optional()->unique()->numerify('#############'),

            'ocupacion_profesion' => $this->faker->jobTitle(),
            'pais_nacimiento' => $this->faker->country(),
            'estado_nacimiento' => $this->faker->state(),
            'municipio_nacimiento' => $this->faker->city(),
            'localidad_nacimiento' => $this->faker->city(),

            'calle' => $this->faker->streetName(),
            'numero_interior' => $this->faker->optional()->buildingNumber(),
            'numero_exterior' => $this->faker->buildingNumber(),
            'colonia' => $this->faker->streetSuffix(),
            'codigo_postal' => $this->faker->postcode(),

            'pais_domicilio' => $this->faker->country(),
            'estado_domicilio' => $this->faker->state(),
            'municipio_domicilio' => $this->faker->city(),
            'localidad_domicilio' => $this->faker->city(),
        ];
    }
}
