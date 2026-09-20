<?php

namespace Database\Factories;

use App\Models\Aportante;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Aportante>
 */
class AportanteFactory extends Factory
{
    protected $model = Aportante::class;

    public function definition(): array
    {
        return [
            'nombre_completo' => fake()->name(),
            'cui_dpi' => (string) fake()->unique()->numerify('#############'),
            'telefono' => fake()->numerify('########'),
            'activo' => true,
        ];
    }
}
