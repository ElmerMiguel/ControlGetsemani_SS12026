<?php

namespace Database\Factories;

use App\Models\Caja;
use App\Models\Transferencia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transferencia>
 */
class TransferenciaFactory extends Factory
{
    protected $model = Transferencia::class;

    public function definition(): array
    {
        return [
            'caja_origen_id' => Caja::factory(),
            'caja_destino_id' => Caja::factory(),
            'fecha' => fake()->date(),
            'monto' => fake()->randomFloat(2, 50, 2000),
            'concepto' => fake()->sentence(3),
            'usuario_id' => User::factory(),
            'anulado_por' => null,
            'motivo_anulacion' => null,
        ];
    }
}
