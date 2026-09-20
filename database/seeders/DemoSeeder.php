<?php

namespace Database\Seeders;

use App\Models\Aportante;
use App\Models\Caja;
use App\Models\CatalogoEgreso;
use App\Models\CatalogoIngreso;
use App\Models\Departamento;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    /**
     * Seed realistic demonstration data for development and grading.
     * Solo se ejecuta si SEED_DEMO=true y APP_ENV=local.
     */
    public function run(): void
    {
        $seedDemo = filter_var(env('SEED_DEMO', false), FILTER_VALIDATE_BOOLEAN);

        if (! $seedDemo || ! app()->environment('local')) {
            $this->command?->info('DemoSeeder: Omitido (requiere SEED_DEMO=true y APP_ENV=local).');

            return;
        }

        $this->command?->info('DemoSeeder: Generando usuarios tesoreros y movimientos ficticios...');

        // Desactivar temporalmente la bitácora para no saturar con datos semilla
        $auditoriaPrevia = config('auditoria.activa', true);
        config(['auditoria.activa' => false]);

        try {
            $password = env('SEED_DEMO_PASSWORD', 'Password123!');
            $cuentasIngreso = CatalogoIngreso::where('activo', true)->where('es_transferencia', false)->get();
            $cuentasEgreso = CatalogoEgreso::where('activo', true)->where('es_transferencia', false)->get();

            // Crear algunos aportantes frecuentes si no existen
            if (Aportante::count() < 10) {
                Aportante::factory()->count(10)->create();
            }
            $aportantes = Aportante::where('activo', true)->get();

            $departamentos = Departamento::with('cajas')->get();

            foreach ($departamentos as $departamento) {
                $slug = Str::slug($departamento->nombre, '_');
                $email = "tesorero.{$slug}@getsemani.test";

                $tesorero = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => "Tesorero {$departamento->nombre}",
                        'password' => Hash::make($password),
                        'must_change_password' => false,
                        'activo' => true,
                    ]
                );

                if (! $tesorero->hasRole('tesorero')) {
                    $tesorero->assignRole('tesorero');
                }

                // Asignar todas las cajas del departamento al tesorero
                $cajasIds = $departamento->cajas->pluck('id')->toArray();
                $tesorero->cajas()->sync($cajasIds);

                // Generar movimientos ficticios para las cajas del departamento
                foreach ($departamento->cajas as $caja) {
                    $this->generarMovimientosParaCaja($caja, $tesorero, $cuentasIngreso, $cuentasEgreso, $aportantes);
                }
            }

            $this->command?->info('DemoSeeder: Finalizado exitosamente.');
        } finally {
            config(['auditoria.activa' => $auditoriaPrevia]);
        }
    }

    /**
     * Genera movimientos realistas distribuidos entre la fecha de apertura y hoy.
     */
    private function generarMovimientosParaCaja(
        Caja $caja,
        User $tesorero,
        $cuentasIngreso,
        $cuentasEgreso,
        $aportantes
    ): void {
        if (! $caja->activa || $cuentasIngreso->isEmpty() || $cuentasEgreso->isEmpty()) {
            return;
        }

        $fechaInicio = $caja->fecha_apertura ? $caja->fecha_apertura->copy() : Carbon::create(now()->year, 1, 1);
        $hoy = Carbon::today();

        if ($fechaInicio->gt($hoy)) {
            return;
        }

        // Si la caja ya tiene movimientos, no duplicar
        if ($caja->ingresos()->exists() || $caja->egresos()->exists()) {
            return;
        }

        $diasTotales = $fechaInicio->diffInDays($hoy);
        if ($diasTotales < 1) {
            $diasTotales = 1;
        }

        // Generar entre 4 y 10 ingresos distribuidos
        $cantidadIngresos = min(10, max(4, (int) ($diasTotales / 15)));
        for ($i = 0; $i < $cantidadIngresos; $i++) {
            $offset = rand(0, $diasTotales);
            $fechaMov = $fechaInicio->copy()->addDays($offset);
            $cuenta = $cuentasIngreso->random();
            $aportante = $aportantes->isNotEmpty() && rand(0, 100) > 30 ? $aportantes->random() : null;

            Ingreso::create([
                'caja_id' => $caja->id,
                'fecha' => $fechaMov,
                'cuenta_ingreso_id' => $cuenta->id,
                'monto' => rand(150, 2500).'.'.str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT),
                'recibo' => 'REC-'.rand(1001, 9999),
                'aportante_id' => $aportante?->id,
                'observaciones' => 'Aporte demostrativo a '.$caja->nombre,
                'usuario_id' => $tesorero->id,
                'transferencia_id' => null,
            ]);
        }

        // Generar entre 3 y 8 egresos distribuidos
        $cantidadEgresos = min(8, max(3, (int) ($diasTotales / 20)));
        for ($j = 0; $j < $cantidadEgresos; $j++) {
            $offset = rand(0, $diasTotales);
            $fechaMov = $fechaInicio->copy()->addDays($offset);
            $cuenta = $cuentasEgreso->random();

            Egreso::create([
                'caja_id' => $caja->id,
                'fecha' => $fechaMov,
                'cuenta_egreso_id' => $cuenta->id,
                'monto' => rand(80, 1200).'.'.str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT),
                'referencia' => 'FAC-'.rand(500, 8000),
                'descripcion' => 'Gasto operativo en cuenta '.$cuenta->nombre,
                'usuario_id' => $tesorero->id,
                'transferencia_id' => null,
            ]);
        }
    }
}
