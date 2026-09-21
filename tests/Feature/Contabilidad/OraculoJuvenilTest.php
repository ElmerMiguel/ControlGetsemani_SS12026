<?php

namespace Tests\Feature\Contabilidad;

use App\Models\Caja;
use App\Models\CatalogoEgreso;
use App\Models\CatalogoIngreso;
use App\Models\Departamento;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\User;
use App\Services\CajaService;
use App\Services\CorteCajaService;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * OraculoJuvenilTest
 *
 * Verifica el oráculo contable oficial con datos agregados reales de 2022 (§12.2):
 * - Caja Juvenil: saldo_apertura 2,534.50 al 2022-01-01
 * - Ingresos anuales: Q 247,711.00
 * - Egresos anuales: Q 249,312.00
 * - Saldo final esperado al cierre de diciembre: Q 933.50
 * - Cadena ininterrumpida de 12 cortes mensuales aprobados verificando continuidad contable.
 */
class OraculoJuvenilTest extends TestCase
{
    use RefreshDatabase;

    protected CajaService $cajaService;

    protected CorteCajaService $corteService;

    protected User $admin;

    protected Caja $caja;

    protected CatalogoIngreso $cuentaIngreso;

    protected CatalogoEgreso $cuentaEgreso;

    protected array $ingresosPorMes = [
        1 => '17017.50',
        2 => '6550.00',
        3 => '22225.50',
        4 => '86049.00',
        5 => '18290.00',
        6 => '20968.00',
        7 => '9500.00',
        8 => '6621.00',
        9 => '8526.00',
        10 => '13240.00',
        11 => '32196.00',
        12 => '6528.00',
    ];

    protected array $egresosPorMes = [
        1 => '4164.50',
        2 => '15293.00',
        3 => '14642.00',
        4 => '72576.00',
        5 => '39440.00',
        6 => '19646.00',
        7 => '7867.00',
        8 => '14924.00',
        9 => '8628.00',
        10 => '6355.00',
        11 => '30131.50',
        12 => '15645.00',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);
        $this->seed(CatalogosSeeder::class);

        $this->cajaService = app(CajaService::class);
        $this->corteService = app(CorteCajaService::class);

        $this->admin = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->admin->assignRole('admin');

        $depto = Departamento::create([
            'nombre' => 'Sociedad de Jóvenes',
            'tipo' => 'comite',
            'activo' => true,
        ]);

        $this->caja = Caja::create([
            'departamento_id' => $depto->id,
            'codigo' => 'CAJ-JUV-2022',
            'nombre' => 'Caja Sociedad de Jóvenes (Histórica 2022)',
            'saldo_apertura' => '2534.50',
            'fecha_apertura' => '2022-01-01',
            'activa' => true,
        ]);
        $this->caja->tesoreros()->attach($this->admin->id);

        $this->cuentaIngreso = CatalogoIngreso::where('es_transferencia', false)->first();
        $this->cuentaEgreso = CatalogoEgreso::where('es_transferencia', false)->first();

        // Sembrar 1 ingreso y 1 egreso mensual
        for ($mes = 1; $mes <= 12; $mes++) {
            $fechaIngreso = sprintf('2022-%02d-10', $mes);
            $fechaEgreso = sprintf('2022-%02d-20', $mes);

            Ingreso::create([
                'caja_id' => $this->caja->id,
                'fecha' => $fechaIngreso,
                'cuenta_ingreso_id' => $this->cuentaIngreso->id,
                'monto' => $this->ingresosPorMes[$mes],
                'recibo' => "REC-2022-{$mes}",
                'observaciones' => "Ingreso global mes {$mes} oráculo",
                'usuario_id' => $this->admin->id,
            ]);

            Egreso::create([
                'caja_id' => $this->caja->id,
                'fecha' => $fechaEgreso,
                'cuenta_egreso_id' => $this->cuentaEgreso->id,
                'monto' => $this->egresosPorMes[$mes],
                'descripcion' => "Egreso global mes {$mes} oráculo",
                'usuario_id' => $this->admin->id,
            ]);
        }
    }

    public function test_oraculo_juvenil_totales_anuales_y_saldo_final(): void
    {
        $resumenAnual = $this->cajaService->resumenPeriodo($this->caja, '2022-01-01', '2022-12-31');

        $this->assertEquals('2534.50', $resumenAnual['saldo_inicial']);
        $this->assertEquals('247711.00', $resumenAnual['ingresos']);
        $this->assertEquals('249312.00', $resumenAnual['egresos']);
        $this->assertEquals('933.50', $resumenAnual['saldo_final']);

        // Saldo al cierre del 31 de diciembre
        $saldoAlCierre = $this->cajaService->saldoAl($this->caja, Carbon::parse('2022-12-31'));
        $this->assertEquals('933.50', $saldoAlCierre);
    }

    public function test_oraculo_juvenil_cadena_de_12_cortes_mensuales_aprobados(): void
    {
        $diasFinMes = [
            1 => 31, 2 => 28, 3 => 31, 4 => 30, 5 => 31, 6 => 30,
            7 => 31, 8 => 31, 9 => 30, 10 => 31, 11 => 30, 12 => 31,
        ];

        $corteAnterior = null;

        for ($mes = 1; $mes <= 12; $mes++) {
            $finMesStr = sprintf('2022-%02d-%02d', $mes, $diasFinMes[$mes]);

            $corte = $this->corteService->solicitar(
                $this->admin,
                $this->caja,
                $finMesStr,
                "Corte mensual correspondiente al mes {$mes} de 2022"
            );

            // Si no es el primer mes, el saldo inicial debe ser idéntico al final del anterior
            if ($corteAnterior !== null) {
                $this->assertEquals(
                    $corteAnterior->saldo_final,
                    $corte->saldo_inicial,
                    "Discontinuidad contable en el inicio del corte del mes {$mes}"
                );
            }

            // Aprobar corte
            $corteAprobado = $this->corteService->aprobar($this->admin, $corte);
            $this->assertEquals('aprobado', $corteAprobado->estado->value);

            $corteAnterior = $corteAprobado;
        }

        // El corte número 12 (Diciembre) debe culminar exactamente con el saldo esperado de 933.50
        $this->assertEquals('933.50', $corteAnterior->saldo_final);
    }
}
