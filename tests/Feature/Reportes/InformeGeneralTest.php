<?php

namespace Tests\Feature\Reportes;

use App\Enums\EstadoCorte;
use App\Models\Caja;
use App\Models\CatalogoEgreso;
use App\Models\CatalogoIngreso;
use App\Models\CorteCaja;
use App\Models\Departamento;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\Transferencia;
use App\Models\User;
use App\Services\InformeGeneralService;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InformeGeneralTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $tesorero;

    protected Departamento $deptoJuvenil;

    protected Departamento $deptoConsejo;

    protected Departamento $deptoFemenil;

    protected Caja $cajaJuvenil;

    protected Caja $cajaConsejo;

    protected Caja $cajaFemenil;

    protected CatalogoIngreso $cuentaIngreso;

    protected CatalogoEgreso $cuentaEgreso;

    protected InformeGeneralService $informeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);

        $this->admin = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->admin->assignRole('admin');

        $this->tesorero = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->tesorero->assignRole('tesorero');

        $this->deptoJuvenil = Departamento::factory()->create(['nombre' => 'Comité Juvenil', 'activo' => true]);
        $this->deptoConsejo = Departamento::factory()->create(['nombre' => 'Consejo Local', 'activo' => true]);
        $this->deptoFemenil = Departamento::factory()->create(['nombre' => 'Comité Femenil', 'activo' => true]);

        $this->cajaJuvenil = Caja::create([
            'departamento_id' => $this->deptoJuvenil->id,
            'codigo' => 'CAJ-JUV-01',
            'nombre' => 'Caja Juvenil 2022',
            'saldo_apertura' => '2534.50',
            'fecha_apertura' => '2022-01-01',
            'activa' => true,
        ]);

        $this->cajaConsejo = Caja::create([
            'departamento_id' => $this->deptoConsejo->id,
            'codigo' => 'CAJ-CON-01',
            'nombre' => 'Diezmo Consejo Local 2022',
            'saldo_apertura' => '5492.50',
            'fecha_apertura' => '2022-01-01',
            'activa' => true,
        ]);

        $this->cajaFemenil = Caja::create([
            'departamento_id' => $this->deptoFemenil->id,
            'codigo' => 'CAJ-FEM-01',
            'nombre' => 'Caja Femenil',
            'saldo_apertura' => '1000.00',
            'fecha_apertura' => '2022-01-01',
            'activa' => true,
        ]);

        $this->tesorero->cajas()->attach($this->cajaJuvenil->id);

        $this->cuentaIngreso = CatalogoIngreso::create(['codigo' => '4.1.01', 'nombre' => 'Diezmos', 'activo' => true]);
        $this->cuentaEgreso = CatalogoEgreso::create(['codigo' => '5.1.01', 'nombre' => 'Ministerio', 'activo' => true]);

        $this->informeService = app(InformeGeneralService::class);
    }

    public function test_admin_puede_ver_pantalla_informe_general(): void
    {
        $response = $this->actingAs($this->admin)->get(route('informe_general.index'));
        $response->assertOk();
        $response->assertSee('Informe General de Asamblea');
        $response->assertSee('Total General Consolidado');
    }

    public function test_tesorero_recibe_403_en_informe_general(): void
    {
        $this->actingAs($this->tesorero)
            ->get(route('informe_general.index'))
            ->assertForbidden();

        $this->actingAs($this->tesorero)
            ->get(route('informe_general.pdf'))
            ->assertForbidden();

        $this->actingAs($this->tesorero)
            ->get(route('informe_general.xlsx'))
            ->assertForbidden();
    }

    public function test_oraculo_informe_general_con_cortes_reales_juvenil_y_consejo_local_2022(): void
    {
        // 1. Simular corte anual aprobado de Juvenil con oráculo oficial 2022
        CorteCaja::create([
            'caja_id' => $this->cajaJuvenil->id,
            'periodo_inicio' => '2022-01-01',
            'periodo_fin' => '2022-12-31',
            'saldo_inicial' => '2534.50',
            'total_ingresos' => '247711.00',
            'total_egresos' => '249312.00',
            'saldo_final' => '933.50',
            'estado' => EstadoCorte::Aprobado,
            'solicitado_por' => $this->admin->id,
            'solicitado_at' => now(),
            'revisado_por' => $this->admin->id,
            'revisado_at' => now(),
        ]);

        // 2. Simular corte anual aprobado de Consejo Local con oráculo oficial 2022
        CorteCaja::create([
            'caja_id' => $this->cajaConsejo->id,
            'periodo_inicio' => '2022-01-01',
            'periodo_fin' => '2022-12-31',
            'saldo_inicial' => '5492.50',
            'total_ingresos' => '883707.00',
            'total_egresos' => '872701.50',
            'saldo_final' => '16498.00',
            'estado' => EstadoCorte::Aprobado,
            'solicitado_por' => $this->admin->id,
            'solicitado_at' => now(),
            'revisado_por' => $this->admin->id,
            'revisado_at' => now(),
        ]);

        $resultado = $this->informeService->generar('2022-01-01', '2022-12-31');

        $deptos = collect($resultado['departamentos'])->keyBy('departamento');

        // Verificación departamento Juvenil
        $this->assertTrue($deptos['Comité Juvenil']['tiene_informacion']);
        $this->assertEquals('2534.50', $deptos['Comité Juvenil']['saldo_inicial']);
        $this->assertEquals('247711.00', $deptos['Comité Juvenil']['total_ingresos']);
        $this->assertEquals('249312.00', $deptos['Comité Juvenil']['total_egresos']);
        $this->assertEquals('933.50', $deptos['Comité Juvenil']['saldo_final']);

        // Verificación departamento Consejo Local
        $this->assertTrue($deptos['Consejo Local']['tiene_informacion']);
        $this->assertEquals('5492.50', $deptos['Consejo Local']['saldo_inicial']);
        $this->assertEquals('883707.00', $deptos['Consejo Local']['total_ingresos']);
        $this->assertEquals('872701.50', $deptos['Consejo Local']['total_egresos']);
        $this->assertEquals('16498.00', $deptos['Consejo Local']['saldo_final']);

        // Verificación de totales consolidados globales de la Asamblea
        $totales = $resultado['totales'];
        $this->assertEquals('8027.00', $totales['saldo_inicial']); // 2534.50 + 5492.50
        $this->assertEquals('1131418.00', $totales['total_ingresos']); // 247711.00 + 883707.00
        $this->assertEquals('1122013.50', $totales['total_egresos']); // 249312.00 + 872701.50
        $this->assertEquals('17431.50', $totales['saldo_final']); // 933.50 + 16498.00
    }

    public function test_departamento_sin_cortes_aprobados_aparece_como_sin_informacion_rn3_03(): void
    {
        // Comité Femenil no tiene cortes aprobados
        $resultado = $this->informeService->generar('2022-01-01', '2022-12-31');
        $deptos = collect($resultado['departamentos'])->keyBy('departamento');

        $this->assertFalse($deptos['Comité Femenil']['tiene_informacion']);
        $this->assertEquals('Sin información del período', $deptos['Comité Femenil']['mensaje']);
        $this->assertNull($deptos['Comité Femenil']['saldo_inicial']);
        $this->assertNull($deptos['Comité Femenil']['saldo_final']);
    }

    public function test_transferencias_internas_se_excluyen_de_totales_externos_rn3_04(): void
    {
        // 1. Simular corte aprobado de caja Juvenil
        CorteCaja::create([
            'caja_id' => $this->cajaJuvenil->id,
            'periodo_inicio' => '2022-01-01',
            'periodo_fin' => '2022-01-31',
            'saldo_inicial' => '2000.00',
            'total_ingresos' => '1000.00',
            'total_egresos' => '500.00',
            'saldo_final' => '2500.00',
            'estado' => EstadoCorte::Aprobado,
            'solicitado_por' => $this->admin->id,
            'solicitado_at' => now(),
        ]);

        // 2. Crear una transferencia entre Juvenil y Consejo
        $transferencia = Transferencia::create([
            'caja_origen_id' => $this->cajaJuvenil->id,
            'caja_destino_id' => $this->cajaConsejo->id,
            'fecha' => '2022-01-15',
            'monto' => '500.00',
            'concepto' => 'Diezmo de diezmo a Consejo Local',
            'usuario_id' => $this->admin->id,
        ]);

        // Crear egreso en Juvenil con transferencia_id
        Egreso::create([
            'caja_id' => $this->cajaJuvenil->id,
            'fecha' => '2022-01-15',
            'cuenta_egreso_id' => $this->cuentaEgreso->id,
            'monto' => '500.00',
            'descripcion' => 'Transferencia a Consejo Local',
            'transferencia_id' => $transferencia->id,
            'usuario_id' => $this->admin->id,
        ]);

        $resultado = $this->informeService->generar('2022-01-01', '2022-01-31');
        $deptos = collect($resultado['departamentos'])->keyBy('departamento');

        // Los egresos externos deben descontar la transferencia interna
        $this->assertEquals('500.00', $deptos['Comité Juvenil']['transferencias_enviadas']);
        $this->assertEquals('0.00', $deptos['Comité Juvenil']['egresos_ordinarios']); // 500 total - 500 transf
        $this->assertEquals('500.00', $resultado['transferencias']['total_monto']);
    }

    public function test_advertencia_cuando_departamento_tiene_movimientos_pero_sin_cortes_aprobados(): void
    {
        // Ingreso en Comité Femenil sin corte aprobado
        Ingreso::create([
            'caja_id' => $this->cajaFemenil->id,
            'fecha' => '2022-06-10',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '300.00',
            'usuario_id' => $this->admin->id,
        ]);

        $resultado = $this->informeService->generar('2022-01-01', '2022-12-31');

        $this->assertNotEmpty($resultado['advertencias']);
        $advertencia = collect($resultado['advertencias'])->firstWhere('departamento', 'Comité Femenil');
        $this->assertNotNull($advertencia);
        $this->assertStringContainsString('Comité Femenil', $advertencia['mensaje']);
        $this->assertStringContainsString('ningún corte de caja aprobado', $advertencia['mensaje']);
    }

    public function test_exportacion_pdf_responde_con_binario_valido(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('informe_general.pdf', ['desde' => '2022-01-01', 'hasta' => '2022-12-31']));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_exportacion_xlsx_responde_con_content_type_correcto(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('informe_general.xlsx', ['desde' => '2022-01-01', 'hasta' => '2022-12-31']));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
    }
}
