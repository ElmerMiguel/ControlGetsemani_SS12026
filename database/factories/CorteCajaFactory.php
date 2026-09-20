<?php

namespace Database\Factories;

use App\Enums\EstadoCorte;
use App\Models\Caja;
use App\Models\CorteCaja;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CorteCaja>
 */
class CorteCajaFactory extends Factory
{
    protected $model = CorteCaja::class;

    public function definition(): array
    {
        $saldoInicial = fake()->randomFloat(2, 500, 5000);
        $totalIngresos = fake()->randomFloat(2, 1000, 10000);
        $totalEgresos = fake()->randomFloat(2, 500, 8000);
        $saldoFinal = $saldoInicial + $totalIngresos - $totalEgresos;

        return [
            'caja_id' => Caja::factory(),
            'periodo_inicio' => fake()->dateTimeBetween('-2 months', '-1 month')->format('Y-m-01'),
            'periodo_fin' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-t'),
            'estado' => fake()->randomElement(EstadoCorte::cases()),
            'saldo_inicial' => $saldoInicial,
            'total_ingresos' => $totalIngresos,
            'total_egresos' => $totalEgresos,
            'saldo_final' => $saldoFinal,
            'solicitado_por' => User::factory(),
            'solicitado_at' => now(),
            'revisado_por' => null,
            'revisado_at' => null,
            'observaciones' => fake()->optional()->sentence(),
        ];
    }
}
