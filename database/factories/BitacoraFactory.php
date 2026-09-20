<?php

namespace Database\Factories;

use App\Models\Bitacora;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bitacora>
 */
class BitacoraFactory extends Factory
{
    protected $model = Bitacora::class;

    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'accion' => fake()->randomElement(['crear', 'actualizar', 'anular', 'login', 'corte_solicitado']),
            'tabla_afectada' => fake()->randomElement(['ingresos', 'egresos', 'cortes_caja', 'cajas']),
            'registro_id' => fake()->numberBetween(1, 100),
            'descripcion' => fake()->sentence(),
            'datos_antes' => null,
            'datos_despues' => ['campo' => fake()->word()],
            'ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'fecha_hora' => now(),
        ];
    }
}
