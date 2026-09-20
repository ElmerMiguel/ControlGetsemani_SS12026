<?php

namespace Database\Factories;

use App\Enums\MedioCaja;
use App\Models\Caja;
use App\Models\Departamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Caja>
 */
class CajaFactory extends Factory
{
    protected $model = Caja::class;

    public function definition(): array
    {
        return [
            'departamento_id' => Departamento::factory(),
            'nombre' => fake()->words(2, true),
            'codigo' => strtoupper(fake()->unique()->bothify('CAJA-###')),
            'medio' => fake()->randomElement(MedioCaja::cases()),
            'saldo_apertura' => fake()->randomFloat(2, 0, 5000),
            'fecha_apertura' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'activa' => true,
        ];
    }
}
