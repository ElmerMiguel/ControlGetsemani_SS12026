<?php

namespace Tests\Feature\Reportes;

use App\Models\Bitacora;
use App\Models\Caja;
use App\Models\CatalogoEgreso;
use App\Models\CatalogoIngreso;
use App\Models\Departamento;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\Transferencia;
use App\Models\User;
use App\Services\ReporteCajaService;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteCajaTest extends TestCase
{
    use RefreshDatabase;

    protected ReporteCajaService $reporteService;

    protected User $admin;

    protected User $tesorero;

    protected User $tesoreroAjeno;

    protected Caja $caja;

    protected Caja $cajaAjena;

    protected CatalogoIngreso $cuentaIngresoA;

    protected CatalogoIngreso $cuentaIngresoB;

    protected CatalogoEgreso $cuentaEgresoA;

    protected CatalogoEgreso $cuentaEgresoB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);

        $this->reporteService = app(ReporteCajaService::class);

        $this->admin = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->admin->assignRole('admin');

        $this->tesorero = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->tesorero->assignRole('tesorero');

        $this->tesoreroAjeno = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->tesoreroAjeno->assignRole('tesorero');

        $depto = Departamento::create([
            'nombre' => 'Ministerio de Jóvenes',
            'tipo' => 'comite',
            'descripcion' => 'Departamento de prueba',
            'activo' => true,
        ]);

        $this->caja = Caja::create([
            'departamento_id' => $depto->id,
            'codigo' => 'CAJ-JUV',
            'nombre' => 'Caja Juvenil',
            'saldo_apertura' => '1000.00',
            'fecha_apertura' => '2026-01-01',
            'activa' => true,
        ]);
        $this->caja->tesoreros()->attach($this->tesorero->id);

        $this->cajaAjena = Caja::create([
            'departamento_id' => $depto->id,
            'codigo' => 'CAJ-CAB',
            'nombre' => 'Caja Caballeros',
            'saldo_apertura' => '500.00',
            'fecha_apertura' => '2026-01-01',
            'activa' => true,
        ]);
        $this->cajaAjena->tesoreros()->attach($this->tesoreroAjeno->id);

        $this->cuentaIngresoA = CatalogoIngreso::create([
            'codigo' => '101',
            'nombre' => 'Diezmos',
            'es_transferencia' => false,
            'activo' => true,
        ]);
        $this->cuentaIngresoB = CatalogoIngreso::create([
            'codigo' => '102',
            'nombre' => 'Ofrendas Especiales',
            'es_transferencia' => false,
            'activo' => true,
        ]);

        $this->cuentaEgresoA = CatalogoEgreso::create([
            'codigo' => '201',
            'nombre' => 'Material Didáctico',
            'es_transferencia' => false,
            'activo' => true,
        ]);
        $this->cuentaEgresoB = CatalogoEgreso::create([
            'codigo' => '202',
            'nombre' => 'Refrigerios y Eventos',
            'es_transferencia' => false,
            'activo' => true,
        ]);
    }

    public function test_totales_del_servicio_coinciden_con_consultas_directas_sql(): void
    {
        // Movimientos ordinarios
        Ingreso::create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-10',
            'cuenta_ingreso_id' => $this->cuentaIngresoA->id,
            'monto' => '500.00',
            'usuario_id' => $this->tesorero->id,
        ]);
        Ingreso::create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-20',
            'cuenta_ingreso_id' => $this->cuentaIngresoB->id,
            'monto' => '300.00',
            'usuario_id' => $this->tesorero->id,
        ]);

        Egreso::create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-15',
            'cuenta_egreso_id' => $this->cuentaEgresoA->id,
            'monto' => '200.00',
            'descripcion' => 'Gasto de prueba',
            'usuario_id' => $this->tesorero->id,
        ]);

        // Movimiento de transferencia (recibida y enviada)
        $transfRecibida = Transferencia::create([
            'caja_origen_id' => $this->cajaAjena->id,
            'caja_destino_id' => $this->caja->id,
            'fecha' => '2026-01-25',
            'monto' => '150.00',
            'concepto' => 'Apoyo inter-cajas',
            'usuario_id' => $this->admin->id,
        ]);
        Ingreso::create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-25',
            'cuenta_ingreso_id' => $this->cuentaIngresoA->id,
            'monto' => '150.00',
            'transferencia_id' => $transfRecibida->id,
            'usuario_id' => $this->admin->id,
        ]);

        // Generar reporte del mes de enero
        $datos = $this->reporteService->generar($this->caja, '2026-01-01', '2026-01-31');

        $this->assertEquals('1000.00', $datos['resumen']['saldo_inicial']);
        $this->assertEquals('800.00', $datos['resumen']['ingresos_ordinarios']);
        $this->assertEquals('150.00', $datos['resumen']['transferencias_recibidas']);
        $this->assertEquals('950.00', $datos['resumen']['total_ingresos']);
        $this->assertEquals('200.00', $datos['resumen']['egresos_ordinarios']);
        $this->assertEquals('0.00', $datos['resumen']['transferencias_enviadas']);
        $this->assertEquals('200.00', $datos['resumen']['total_egresos']);
        $this->assertEquals('750.00', $datos['resumen']['neto_periodo']);
        $this->assertEquals('1750.00', $datos['resumen']['saldo_final']);

        // Comprobar desglose por cuenta
        $this->assertCount(2, $datos['ingresos_por_cuenta']);
        $this->assertEquals('500.00', $datos['ingresos_por_cuenta'][0]['total']);
        $this->assertEquals('300.00', $datos['ingresos_por_cuenta'][1]['total']);

        // Comprobar transferencias
        $this->assertCount(1, $datos['transferencias']['recibidas']);
        $this->assertEquals('150.00', $datos['transferencias']['total_recibidas']);
    }

    public function test_matriz_suma_exactamente_igual_a_totales_por_cuenta_y_mes(): void
    {
        // Crear egresos distribuidos en 3 meses diferentes
        Egreso::create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-10',
            'cuenta_egreso_id' => $this->cuentaEgresoA->id,
            'monto' => '100.00',
            'descripcion' => 'Material didáctico enero',
            'usuario_id' => $this->tesorero->id,
        ]);
        Egreso::create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-02-15',
            'cuenta_egreso_id' => $this->cuentaEgresoA->id,
            'monto' => '150.00',
            'descripcion' => 'Material didáctico febrero',
            'usuario_id' => $this->tesorero->id,
        ]);
        Egreso::create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-02-20',
            'cuenta_egreso_id' => $this->cuentaEgresoB->id,
            'monto' => '250.00',
            'descripcion' => 'Refrigerios febrero',
            'usuario_id' => $this->tesorero->id,
        ]);
        Egreso::create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-03-05',
            'cuenta_egreso_id' => $this->cuentaEgresoB->id,
            'monto' => '300.00',
            'descripcion' => 'Refrigerios marzo',
            'usuario_id' => $this->tesorero->id,
        ]);

        $datos = $this->reporteService->generar($this->caja, '2026-01-01', '2026-03-31');
        $matriz = $datos['matriz_egresos'];

        $this->assertTrue($matriz['cruza_meses']);
        $this->assertCount(3, $matriz['meses']);

        // Total general de la matriz debe ser 800.00
        $this->assertEquals('800.00', $matriz['total_general']);
        $this->assertEquals('800.00', $datos['total_egresos_cuentas']);

        // Suma de columnas mensuales debe coincidir
        $this->assertEquals('100.00', $matriz['totales_mes']['2026-01']);
        $this->assertEquals('400.00', $matriz['totales_mes']['2026-02']);
        $this->assertEquals('300.00', $matriz['totales_mes']['2026-03']);

        // Suma de filas por cuenta debe coincidir
        $totalFilas = '0.00';
        foreach ($matriz['filas'] as $fila) {
            $totalFilas = bcadd($totalFilas, $fila['total'], 2);
        }
        $this->assertEquals('800.00', $totalFilas);
    }

    public function test_vista_html_del_reporte_renderiza_correctamente(): void
    {
        $response = $this->actingAs($this->tesorero)
            ->get(route('reportes.caja', ['caja_id' => $this->caja->id]));

        $response->assertOk();
        $response->assertSee('Reporte Financiero por Caja');
        $response->assertSee('Caja Juvenil');
    }

    public function test_vista_html_del_reporte_renderiza_correctamente_para_admin_sin_parametros(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('reportes.caja'));

        $response->assertOk();
        $response->assertSee('Reporte Financiero por Caja');
    }

    public function test_pdf_export_responde_con_content_type_application_pdf(): void
    {
        $response = $this->actingAs($this->tesorero)
            ->get(route('reportes.caja.pdf', [
                'caja_id' => $this->caja->id,
                'desde' => '2026-01-01',
                'hasta' => '2026-01-31',
            ]));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_xlsx_export_responde_con_content_type_correcto(): void
    {
        $response = $this->actingAs($this->tesorero)
            ->get(route('reportes.caja.xlsx', [
                'caja_id' => $this->caja->id,
                'desde' => '2026-01-01',
                'hasta' => '2026-01-31',
            ]));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
    }

    public function test_tesorero_no_puede_ver_ni_exportar_caja_ajena_idor(): void
    {
        // Tesorero intentando ver HTML de caja ajena
        $this->actingAs($this->tesorero)
            ->get(route('reportes.caja', ['caja_id' => $this->cajaAjena->id]))
            ->assertForbidden();

        // Tesorero intentando exportar PDF de caja ajena
        $this->actingAs($this->tesorero)
            ->get(route('reportes.caja.pdf', ['caja_id' => $this->cajaAjena->id]))
            ->assertForbidden();

        // Tesorero intentando exportar Excel de caja ajena
        $this->actingAs($this->tesorero)
            ->get(route('reportes.caja.xlsx', ['caja_id' => $this->cajaAjena->id]))
            ->assertForbidden();
    }

    public function test_rango_invalido_es_rechazado(): void
    {
        // Fecha inicio > fecha fin
        $response = $this->actingAs($this->tesorero)
            ->get(route('reportes.caja', [
                'caja_id' => $this->caja->id,
                'desde' => '2026-02-01',
                'hasta' => '2026-01-01',
            ]));

        $response->assertSessionHasErrors('desde');

        // Rango mayor a 366 días
        $responseMasDeUnAnio = $this->actingAs($this->tesorero)
            ->get(route('reportes.caja', [
                'caja_id' => $this->caja->id,
                'desde' => '2025-01-01',
                'hasta' => '2026-02-01',
            ]));

        $responseMasDeUnAnio->assertSessionHasErrors('hasta');
    }

    public function test_exportaciones_quedan_registradas_en_bitacora(): void
    {
        $conteoInicial = Bitacora::where('accion', 'reporte.exportado')->count();

        // Exportar PDF
        $this->actingAs($this->tesorero)
            ->get(route('reportes.caja.pdf', [
                'caja_id' => $this->caja->id,
                'desde' => '2026-01-01',
                'hasta' => '2026-01-31',
            ]));

        // Exportar XLSX
        $this->actingAs($this->tesorero)
            ->get(route('reportes.caja.xlsx', [
                'caja_id' => $this->caja->id,
                'desde' => '2026-01-01',
                'hasta' => '2026-01-31',
            ]));

        $conteoFinal = Bitacora::where('accion', 'reporte.exportado')->count();
        $this->assertEquals($conteoInicial + 2, $conteoFinal);

        $ultimoRegistro = Bitacora::where('accion', 'reporte.exportado')->latest('id')->first();
        $this->assertEquals('xlsx', $ultimoRegistro->datos_despues['formato']);
        $this->assertEquals($this->caja->id, $ultimoRegistro->datos_despues['caja_id']);
    }

    /**
     * Oráculo de datos reales (ARQUITECTURA §12.2):
     * Juvenil 2022 — Saldo apertura 2,534.50, esperado final 933.50.
     */
    public function test_oraculo_informe_general_2022_juvenil(): void
    {
        $cajaJuvenil = Caja::create([
            'departamento_id' => $this->caja->departamento_id,
            'codigo' => 'CAJ-ORACULO',
            'nombre' => 'Juvenil 2022',
            'saldo_apertura' => '2534.50',
            'fecha_apertura' => '2022-01-01',
            'activa' => true,
        ]);
        $cajaJuvenil->tesoreros()->attach($this->tesorero->id);

        $ingresosMes = [
            1 => '17017.50', 2 => '6550.00', 3 => '22225.50', 4 => '86049.00',
            5 => '18290.00', 6 => '20968.00', 7 => '9500.00', 8 => '6621.00',
            9 => '8526.00', 10 => '13240.00', 11 => '32196.00', 12 => '6528.00',
        ];

        $egresosMes = [
            1 => '4164.50', 2 => '15293.00', 3 => '14642.00', 4 => '72576.00',
            5 => '39440.00', 6 => '19646.00', 7 => '7867.00', 8 => '14924.00',
            9 => '8628.00', 10 => '6355.00', 11 => '30131.50', 12 => '15645.00',
        ];

        foreach ($ingresosMes as $mes => $monto) {
            $fecha = sprintf('2022-%02d-15', $mes);
            Ingreso::create([
                'caja_id' => $cajaJuvenil->id,
                'fecha' => $fecha,
                'cuenta_ingreso_id' => $this->cuentaIngresoA->id,
                'monto' => $monto,
                'usuario_id' => $this->admin->id,
            ]);
        }

        foreach ($egresosMes as $mes => $monto) {
            $fecha = sprintf('2022-%02d-20', $mes);
            Egreso::create([
                'caja_id' => $cajaJuvenil->id,
                'fecha' => $fecha,
                'cuenta_egreso_id' => $this->cuentaEgresoA->id,
                'monto' => $monto,
                'descripcion' => 'Egreso mensual oráculo '.$mes,
                'usuario_id' => $this->admin->id,
            ]);
        }

        $datos = $this->reporteService->generar($cajaJuvenil, '2022-01-01', '2022-12-31');

        $this->assertEquals('2534.50', $datos['resumen']['saldo_inicial']);
        $this->assertEquals('247711.00', $datos['resumen']['total_ingresos']);
        $this->assertEquals('249312.00', $datos['resumen']['total_egresos']);
        $this->assertEquals('933.50', $datos['resumen']['saldo_final']);
        $this->assertEquals('249312.00', $datos['matriz_egresos']['total_general']);
    }
}
