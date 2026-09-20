<?php

namespace Tests\Feature\Cajas;

use App\Models\Aportante;
use App\Models\Caja;
use App\Models\CatalogoEgreso;
use App\Models\CatalogoIngreso;
use App\Models\Departamento;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\Transferencia;
use App\Models\User;
use App\Services\CajaService;
use Carbon\Carbon;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CajaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CajaService $cajaService;

    protected User $admin;

    protected User $tesorero;

    protected Departamento $depto;

    protected Caja $caja;

    protected CatalogoIngreso $cuentaIngreso;

    protected CatalogoEgreso $cuentaEgreso1;

    protected CatalogoEgreso $cuentaEgreso2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);

        $this->cajaService = app(CajaService::class);

        $this->admin = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->admin->assignRole('admin');

        $this->tesorero = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->tesorero->assignRole('tesorero');

        $this->depto = Departamento::factory()->create(['nombre' => 'Consejo Local']);

        $this->caja = Caja::factory()->create([
            'departamento_id' => $this->depto->id,
            'nombre' => 'Caja General',
            'codigo' => 'CJ-001',
            'saldo_apertura' => '1000.00',
            'fecha_apertura' => '2026-01-01',
            'activa' => true,
        ]);
        $this->caja->tesoreros()->attach($this->tesorero->id);

        $this->cuentaIngreso = CatalogoIngreso::factory()->create([
            'codigo' => '101',
            'nombre' => 'Diezmos',
            'es_transferencia' => false,
        ]);

        $this->cuentaEgreso1 = CatalogoEgreso::factory()->create([
            'codigo' => '201',
            'nombre' => 'Servicios Básicos',
            'es_transferencia' => false,
        ]);

        $this->cuentaEgreso2 = CatalogoEgreso::factory()->create([
            'codigo' => '202',
            'nombre' => 'Materiales',
            'es_transferencia' => false,
        ]);
    }

    public function test_saldo_actual_calcula_correctamente_segun_rn_02(): void
    {
        // Apertura: 1000.00
        // + Ingreso 1: 500.00
        Ingreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-05',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '500.00',
            'usuario_id' => $this->admin->id,
        ]);

        // + Ingreso 2: 250.50
        Ingreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-10',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '250.50',
            'usuario_id' => $this->admin->id,
        ]);

        // - Egreso 1: 300.00
        Egreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-12',
            'cuenta_egreso_id' => $this->cuentaEgreso1->id,
            'monto' => '300.00',
            'usuario_id' => $this->admin->id,
        ]);

        // Esperado: 1000.00 + 500.00 + 250.50 - 300.00 = 1450.50
        $saldo = $this->cajaService->saldoActual($this->caja);
        $this->assertSame('1450.50', $saldo);
    }

    public function test_saldo_actual_excluye_movimientos_anulados(): void
    {
        Ingreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-05',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '500.00',
            'usuario_id' => $this->admin->id,
        ]);

        $ingresoAnulado = Ingreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-08',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '1000.00',
            'usuario_id' => $this->admin->id,
        ]);
        $ingresoAnulado->delete(); // Soft delete

        $egresoAnulado = Egreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-09',
            'cuenta_egreso_id' => $this->cuentaEgreso1->id,
            'monto' => '400.00',
            'usuario_id' => $this->admin->id,
        ]);
        $egresoAnulado->delete(); // Soft delete

        // Solo cuenta apertura (1000.00) + ingreso activo (500.00) = 1500.00
        $saldo = $this->cajaService->saldoActual($this->caja);
        $this->assertSame('1500.00', $saldo);
    }

    public function test_saldo_actual_incluye_transferencias_internas_en_saldo_de_caja(): void
    {
        $transferencia = Transferencia::factory()->create([
            'caja_origen_id' => $this->caja->id,
            'monto' => '200.00',
            'fecha' => '2026-01-15',
        ]);

        Egreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-15',
            'cuenta_egreso_id' => $this->cuentaEgreso1->id,
            'monto' => '200.00',
            'transferencia_id' => $transferencia->id,
            'usuario_id' => $this->admin->id,
        ]);

        // Apertura: 1000.00 - Egreso por transferencia: 200.00 = 800.00
        $saldo = $this->cajaService->saldoActual($this->caja);
        $this->assertSame('800.00', $saldo);
    }

    public function test_saldo_al_cierre_de_fecha_limite(): void
    {
        // Antes de la fecha de apertura (apertura: 2026-01-01)
        $this->assertSame('0.00', $this->cajaService->saldoAl($this->caja, '2025-12-31'));

        // El día de apertura sin movimientos: debe ser el saldo_apertura
        $this->assertSame('1000.00', $this->cajaService->saldoAl($this->caja, '2026-01-01'));

        // Agregamos movimientos en distintas fechas
        Ingreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-01',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '200.00',
            'usuario_id' => $this->admin->id,
        ]);

        Ingreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-15',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '300.00',
            'usuario_id' => $this->admin->id,
        ]);

        Egreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-20',
            'cuenta_egreso_id' => $this->cuentaEgreso1->id,
            'monto' => '150.00',
            'usuario_id' => $this->admin->id,
        ]);

        // Saldo al 2026-01-01: 1000 + 200 = 1200.00
        $this->assertSame('1200.00', $this->cajaService->saldoAl($this->caja, '2026-01-01'));

        // Saldo al 2026-01-15: 1000 + 200 + 300 = 1500.00
        $this->assertSame('1500.00', $this->cajaService->saldoAl($this->caja, '2026-01-15'));

        // Saldo al 2026-01-31: 1500 - 150 = 1350.00
        $this->assertSame('1350.00', $this->cajaService->saldoAl($this->caja, '2026-01-31'));
    }

    public function test_resumen_periodo_calcula_segun_rn_03(): void
    {
        // Movimientos en enero
        Ingreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-10',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '500.00',
            'usuario_id' => $this->admin->id,
        ]);

        Egreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-20',
            'cuenta_egreso_id' => $this->cuentaEgreso1->id,
            'monto' => '200.00',
            'usuario_id' => $this->admin->id,
        ]);

        // Periodo 1: Mes de enero completo (desde fecha de apertura)
        $resumenEnero = $this->cajaService->resumenPeriodo($this->caja, Carbon::parse('2026-01-01'), Carbon::parse('2026-01-31'));

        $this->assertSame('1000.00', $resumenEnero['saldo_inicial']);
        $this->assertSame('500.00', $resumenEnero['ingresos']);
        $this->assertSame('200.00', $resumenEnero['egresos']);
        $this->assertSame('1300.00', $resumenEnero['saldo_final']);

        // Movimientos en febrero
        Ingreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-02-05',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '400.00',
            'usuario_id' => $this->admin->id,
        ]);

        // Periodo 2: Febrero (inicia posterior a fecha de apertura)
        $resumenFebrero = $this->cajaService->resumenPeriodo($this->caja, Carbon::parse('2026-02-01'), Carbon::parse('2026-02-28'));

        // Saldo inicial de febrero debe ser el saldo final de enero (1300.00)
        $this->assertSame('1300.00', $resumenFebrero['saldo_inicial']);
        $this->assertSame('400.00', $resumenFebrero['ingresos']);
        $this->assertSame('0.00', $resumenFebrero['egresos']);
        $this->assertSame('1700.00', $resumenFebrero['saldo_final']);
    }

    public function test_serie_mensual_retorna_12_posiciones_y_excluye_transferencias(): void
    {
        // Ingreso normal en enero: 100.00
        Ingreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-10',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '100.00',
            'transferencia_id' => null,
            'usuario_id' => $this->admin->id,
        ]);

        // Ingreso de transferencia en enero (cuenta 900): 500.00
        $transferencia = Transferencia::factory()->create(['caja_destino_id' => $this->caja->id]);
        Ingreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-15',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '500.00',
            'transferencia_id' => $transferencia->id,
            'usuario_id' => $this->admin->id,
        ]);

        // Egreso normal en febrero: 50.00
        Egreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-02-12',
            'cuenta_egreso_id' => $this->cuentaEgreso1->id,
            'monto' => '50.00',
            'transferencia_id' => null,
            'usuario_id' => $this->admin->id,
        ]);

        $serie = $this->cajaService->serieMensual($this->caja, 2026, $this->admin);

        $this->assertCount(12, $serie['labels']);
        $this->assertCount(12, $serie['ingresos']);
        $this->assertCount(12, $serie['egresos']);

        // Enero (índice 0): solo el ingreso normal (100.00), excluye la transferencia (500.00)
        $this->assertEquals(100.0, $serie['ingresos'][0]);
        // Febrero (índice 1): egreso normal (50.00)
        $this->assertEquals(50.0, $serie['egresos'][1]);
    }

    public function test_top_egresos_ordena_por_cuenta_y_limita_resultados(): void
    {
        // 3 egresos en cuenta 1 (Servicios Básicos): 100 + 200 + 300 = 600
        Egreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-05',
            'cuenta_egreso_id' => $this->cuentaEgreso1->id,
            'monto' => '100.00',
            'usuario_id' => $this->admin->id,
        ]);
        Egreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-10',
            'cuenta_egreso_id' => $this->cuentaEgreso1->id,
            'monto' => '200.00',
            'usuario_id' => $this->admin->id,
        ]);
        Egreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-15',
            'cuenta_egreso_id' => $this->cuentaEgreso1->id,
            'monto' => '300.00',
            'usuario_id' => $this->admin->id,
        ]);

        // 1 egreso en cuenta 2 (Materiales): 150
        Egreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-18',
            'cuenta_egreso_id' => $this->cuentaEgreso2->id,
            'monto' => '150.00',
            'usuario_id' => $this->admin->id,
        ]);

        $top = $this->cajaService->topEgresos($this->caja, '2026-01-01', '2026-01-31', 5);

        $this->assertCount(2, $top);
        $this->assertSame('201', $top[0]['codigo']);
        $this->assertSame('600.00', $top[0]['total']);
        $this->assertSame('202', $top[1]['codigo']);
        $this->assertSame('150.00', $top[1]['total']);
    }

    public function test_caja_show_muestra_informacion_en_tiempo_real(): void
    {
        $aportante = Aportante::factory()->create(['nombre_completo' => 'Hermano Juan Perez']);

        Ingreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-05',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '500.00',
            'aportante_id' => $aportante->id,
            'usuario_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('cajas.show', $this->caja));

        $response->assertOk();
        $response->assertSee('Caja General');
        $response->assertSee('Saldo Actual (RN-02)');
        $response->assertSee('Q 1,500.00');
        $response->assertSee('Hermano Juan Perez');
    }

    public function test_caja_show_muestra_alerta_roja_si_saldo_es_negativo_rn_10(): void
    {
        // Generamos un egreso mayor al saldo de apertura
        Egreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-05',
            'cuenta_egreso_id' => $this->cuentaEgreso1->id,
            'monto' => '1500.00',
            'usuario_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('cajas.show', $this->caja));

        $response->assertOk();
        $response->assertSee('Alerta: Saldo Negativo (RN-10)');
        $response->assertSee('-Q 500.00');
    }

    public function test_tesorero_recibe_403_al_intentar_ver_caja_ajena_idor(): void
    {
        $cajaAjena = Caja::factory()->create([
            'departamento_id' => $this->depto->id,
            'nombre' => 'Caja Secreta',
            'activa' => true,
        ]);

        $response = $this->actingAs($this->tesorero)->get(route('cajas.show', $cajaAjena));
        $response->assertForbidden();
    }

    public function test_dashboard_carga_para_administrador_con_metricas_globales(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Saldo Global Consolidado');
        $response->assertSee('Saldos por Departamento');
        $response->assertSee('Flujo Mensual Global');
    }

    public function test_dashboard_carga_para_tesorero_con_sus_cajas_y_grafica(): void
    {
        // Asignamos una segunda caja para verificar que el botón "Seleccionar" de caja inactiva funciona y no crashea
        $cajaSecundaria = Caja::factory()->create([
            'departamento_id' => $this->depto->id,
            'nombre' => 'Caja Secundaria',
            'codigo' => 'CJ-002',
            'saldo_apertura' => '250.00',
            'fecha_apertura' => '2026-01-01',
            'activa' => true,
        ]);
        $cajaSecundaria->tesoreros()->attach($this->tesorero->id);

        $response = $this->actingAs($this->tesorero)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Mis Cajas Asignadas');
        $response->assertSee('Caja General');
        $response->assertSee('Caja Secundaria');
        $response->assertSee('Seleccionar');
        $response->assertSee('Top 5 Egresos del Mes');
    }
}
