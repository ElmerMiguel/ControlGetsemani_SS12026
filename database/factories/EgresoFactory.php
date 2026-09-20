<?php

namespace Database\Factories;

use App\Models\Caja;
use App\Models\CatalogoEgreso;
use App\Models\Egreso;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Egreso>
 */
class EgresoFactory extends Factory
{
    protected $model = Egreso::class;

    public function definition(): array
    {
        return [
            'caja_id' => Caja::factory(),
            'fecha' => fake()->date(),
            'cuenta_egreso_id' => CatalogoEgreso::factory(),
            'monto' => fake()->randomFloat(2, 5, 3000),
            'descripcion' => fake()->sentence(4),
            'referencia' => fake()->optional()->bothify('FAC-####'),
            'usuario_id' => User::factory(),
            'transferencia_id' => null,
            'anulado_por' => null,
            'motivo_anulacion' => null,
        ];
    }
}
