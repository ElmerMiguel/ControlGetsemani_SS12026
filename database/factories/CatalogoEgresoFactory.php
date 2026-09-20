<?php

namespace Database\Factories;

use App\Models\CatalogoEgreso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatalogoEgreso>
 */
class CatalogoEgresoFactory extends Factory
{
    protected $model = CatalogoEgreso::class;

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
