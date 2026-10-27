<?php

namespace Database\Factories;

use App\Enums\EstadoBien;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inventario>
 */
class InventarioFactory extends Factory
{
    protected $model = Inventario::class;

    public function definition(): array
    {
        return [
            'departamento_id' => Departamento::factory(),
            'codigo' => 'ACT-'.fake()->unique()->numerify('#####'),
            'nombre' => fake()->words(3, true),
            'descripcion' => fake()->sentence(),
            'valor_estimado' => fake()->randomFloat(2, 50, 5000),
            'estado' => fake()->randomElement(EstadoBien::cases()),
            'fecha_registro' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'usuario_id' => User::factory(),
        ];
    }
}
