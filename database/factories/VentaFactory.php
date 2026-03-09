<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\Predio;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Venta>
 */
class VentaFactory extends Factory
{
    protected $model = Venta::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'aval_id' => Person::factory(),
            'predio_id' => Predio::factory(),
            'user_id' => User::factory(),
            'metodo_pago' => 'meses',
            'costo_lote' => $this->faker->randomFloat(2, 50000, 200000),
            'enganche' => $this->faker->randomFloat(2, 5000, 20000),
            'fecha_primer_abono' => $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'meses_a_pagar' => $this->faker->randomElement([12, 18, 24]),
            'estado' => 'pagando',
        ];
    }
}
