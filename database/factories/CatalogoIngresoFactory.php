<?php

namespace Database\Factories;

use App\Models\CatalogoIngreso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatalogoIngreso>
 */
class CatalogoIngresoFactory extends Factory
{
    protected $model = CatalogoIngreso::class;

    public function definition(): array
    {
        return [
            'codigo' => (string) fake()->unique()->numberBetween(100, 899),
            'nombre' => fake()->words(2, true),
            'es_transferencia' => false,
            'activo' => true,
        ];
    }
}
