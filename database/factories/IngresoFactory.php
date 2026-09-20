<?php

namespace Database\Factories;

use App\Models\Aportante;
use App\Models\Caja;
use App\Models\CatalogoIngreso;
use App\Models\Ingreso;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingreso>
 */
class IngresoFactory extends Factory
{
    protected $model = Ingreso::class;

    public function definition(): array
    {
        return [
            'caja_id' => Caja::factory(),
            'fecha' => fake()->date(),
            'cuenta_ingreso_id' => CatalogoIngreso::factory(),
            'monto' => fake()->randomFloat(2, 10, 5000),
            'recibo' => 'Recibo No. '.fake()->numberBetween(100, 999),
            'aportante_id' => fake()->boolean(60) ? Aportante::factory() : null,
            'observaciones' => fake()->optional()->sentence(),
            'usuario_id' => User::factory(),
            'transferencia_id' => null,
            'anulado_por' => null,
            'motivo_anulacion' => null,
        ];
    }
}
