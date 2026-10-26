<?php

namespace Database\Factories;

use App\Models\Aportante;
use App\Models\ComprobanteDonacion;
use App\Models\Ingreso;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComprobanteDonacion>
 */
class ComprobanteDonacionFactory extends Factory
{
    protected $model = ComprobanteDonacion::class;

    public function definition(): array
    {
        $numero = 'COMP-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT);

        return [
            'ingreso_id' => Ingreso::factory(),
            'aportante_id' => Aportante::factory(),
            'numero' => $numero,
            'generado_en' => now(),
            'generado_por' => User::factory(),
            'enviado_en' => null,
            'ruta_pdf' => "comprobantes/{$numero}.pdf",
        ];
    }
}
