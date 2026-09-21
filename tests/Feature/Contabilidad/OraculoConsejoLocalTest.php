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
 * OraculoConsejoLocalTest
 *
 * Verifica el oráculo contable oficial con datos agregados de diezmos del Consejo Local 2022 (§12.2):
 * - Caja Diezmo Consejo Local: saldo_apertura 5,492.50 al 2022-01-01
 * - Ingresos anuales: Q 883,707.00
 * - Egresos anuales: Q 872,701.50
 * - Saldo final esperado al cierre de diciembre: Q 16,498.00
 * - Cadena ininterrumpida de 12 cortes mensuales aprobados verificando continuidad contable.
 */
class OraculoConsejoLocalTest extends TestCase
{
    use RefreshDatabase;

    protected CajaService $cajaService;

    protected CorteCajaService $corteService;

    protected User $admin;

    protected Caja $caja;

    protected CatalogoIngreso $cuentaIngreso;

    protected CatalogoEgreso $cuentaEgreso;

    protected array $ingresosPorMes = [
        1 => '93629.00',
        2 => '71471.00',
        3 => '105831.00',
        4 => '37556.00',
        5 => '80282.00',
        6 => '32714.00',
        7 => '59032.50',
        8 => '85858.00',
        9 => '75415.00',
        10 => '53233.00',
        11 => '45189.00',
        12 => '143496.50',
    ];

    protected array $egresosPorMes = [
        1 => '99844.90',
        2 => '61572.10',
        3 => '77511.10',
        4 => '63282.00',
        5 => '82293.00',
        6 => '42054.90',
        7 => '59420.00',
        8 => '78258.00',
        9 => '79314.50',
        10 => '51415.00',
        11 => '50702.00',
        12 => '127034.00',
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
            'nombre' => 'Consejo Local de Ancianos',
            'tipo' => 'consejo',
            'activo' => true,
        ]);

        $this->caja = Caja::create([
            'departamento_id' => $depto->id,
            'codigo' => 'CAJ-CONSEJO-2022',
            'nombre' => 'Diezmo Consejo Local (Histórico 2022)',
            'saldo_apertura' => '5492.50',
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
                'recibo' => "REC-CL-{$mes}",
                'observaciones' => "Diezmos agregados mes {$mes} oráculo",
                'usuario_id' => $this->admin->id,
            ]);

            Egreso::create([
                'caja_id' => $this->caja->id,
                'fecha' => $fechaEgreso,
                'cuenta_egreso_id' => $this->cuentaEgreso->id,
                'monto' => $this->egresosPorMes[$mes],
                'descripcion' => "Egresos agregados mes {$mes} oráculo",
                'usuario_id' => $this->admin->id,
            ]);
        }
    }

    public function test_oraculo_consejo_local_totales_anuales_y_saldo_final(): void
    {
        $resumenAnual = $this->cajaService->resumenPeriodo($this->caja, '2022-01-01', '2022-12-31');

        $this->assertEquals('5492.50', $resumenAnual['saldo_inicial']);
        $this->assertEquals('883707.00', $resumenAnual['ingresos']);
        $this->assertEquals('872701.50', $resumenAnual['egresos']);
        $this->assertEquals('16498.00', $resumenAnual['saldo_final']);

        // Saldo al cierre del 31 de diciembre
        $saldoAlCierre = $this->cajaService->saldoAl($this->caja, Carbon::parse('2022-12-31'));
        $this->assertEquals('16498.00', $saldoAlCierre);
    }

    public function test_oraculo_consejo_local_cadena_de_12_cortes_mensuales_aprobados(): void
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

        // El corte número 12 (Diciembre) debe culminar exactamente con el saldo esperado de 16,498.00
        $this->assertEquals('16498.00', $corteAnterior->saldo_final);
    }
}
