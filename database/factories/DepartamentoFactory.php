<?php

namespace Database\Factories;

use App\Enums\TipoDepartamento;
use App\Models\Departamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Departamento>
 */
class DepartamentoFactory extends Factory
{
    protected $model = Departamento::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(3, true),
            'tipo' => fake()->randomElement(TipoDepartamento::cases()),
            'descripcion' => fake()->sentence(),
            'activo' => true,
        ];
    }
}
