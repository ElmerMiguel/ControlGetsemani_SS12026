<?php

namespace Tests\Feature\Cortes;

use App\Enums\EstadoCorte;
use App\Exceptions\DescuadreSnapshotException;
use App\Exceptions\PeriodoBloqueadoException;
use App\Exceptions\TransicionInvalidaException;
use App\Models\Caja;
use App\Models\CatalogoEgreso;
use App\Models\CatalogoIngreso;
use App\Models\Departamento;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\User;
use App\Notifications\CorteAprobadoNotification;
use App\Notifications\CorteReabiertoNotification;
use App\Notifications\CorteRechazadoNotification;
use App\Notifications\CorteSolicitadoNotification;
use App\Services\CorteCajaService;
use App\Services\MovimientoService;
use Carbon\Carbon;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CorteCajaTest extends TestCase
{
    use RefreshDatabase;

    protected CorteCajaService $corteService;

    protected MovimientoService $movimientoService;

    protected User $admin;

    protected User $tesorero;

    protected User $tesoreroAjeno;

    protected Caja $caja;

    protected Caja $cajaAjena;

    protected CatalogoIngreso $cuentaIngreso;

    protected CatalogoEgreso $cuentaEgreso;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);

        $this->corteService = app(CorteCajaService::class);
        $this->movimientoService = app(MovimientoService::class);

        $this->admin = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->admin->assignRole('admin');

        $this->tesorero = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->tesorero->assignRole('tesorero');

        $this->tesoreroAjeno = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->tesoreroAjeno->assignRole('tesorero');

        $depto = Departamento::factory()->create();

        $this->caja = Caja::factory()->create([
            'departamento_id' => $depto->id,
            'nombre' => 'Caja Central',
            'codigo' => 'CJ-CEN',
            'saldo_apertura' => '1000.00',
            'fecha_apertura' => '2026-01-01',
            'activa' => true,
        ]);
        $this->caja->tesoreros()->attach($this->tesorero->id);

        $this->cajaAjena = Caja::factory()->create([
            'departamento_id' => $depto->id,
            'nombre' => 'Caja Ajena',
            'codigo' => 'CJ-AJE',
            'saldo_apertura' => '500.00',
            'fecha_apertura' => '2026-01-01',
            'activa' => true,
        ]);
        $this->cajaAjena->tesoreros()->attach($this->tesoreroAjeno->id);

        $this->cuentaIngreso = CatalogoIngreso::factory()->create(['es_transferencia' => false, 'activo' => true]);
        $this->cuentaEgreso = CatalogoEgreso::factory()->create(['es_transferencia' => false, 'activo' => true]);
    }

    public function test_snapshot_calculado_correctamente_al_solicitar_corte(): void
    {
        Ingreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-10',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '400.00',
            'usuario_id' => $this->tesorero->id,
        ]);

        Egreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-15',
            'cuenta_egreso_id' => $this->cuentaEgreso->id,
            'monto' => '150.00',
            'usuario_id' => $this->tesorero->id,
        ]);

        $corte = $this->corteService->solicitar($this->tesorero, $this->caja, '2026-01-31', 'Primer corte del año');

        $this->assertSame(EstadoCorte::Pendiente, $corte->estado);
        $this->assertSame('1000.00', (string) $corte->saldo_inicial);
        $this->assertSame('400.00', (string) $corte->total_ingresos);
        $this->assertSame('150.00', (string) $corte->total_egresos);
        $this->assertSame('1250.00', (string) $corte->saldo_final);
        $this->assertSame($this->tesorero->id, $corte->solicitado_por);
    }

    public function test_continuidad_contable_tres_cortes_consecutivos(): void
    {
        // Mes 1: Enero
        Ingreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-15',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '500.00',
            'usuario_id' => $this->tesorero->id,
        ]);
        $corte1 = $this->corteService->solicitar($this->tesorero, $this->caja, '2026-01-31');
        $this->corteService->aprobar($this->admin, $corte1);

        $this->assertSame('1000.00', (string) $corte1->saldo_inicial);
        $this->assertSame('1500.00', (string) $corte1->saldo_final);

        // Periodo sugerido para corte 2 debe iniciar el 2026-02-01
        $sugerido2 = $this->corteService->periodoSugerido($this->caja);
        $this->assertSame('2026-02-01', $sugerido2['inicio']->format('Y-m-d'));

        // Mes 2: Febrero
        Egreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-02-10',
            'cuenta_egreso_id' => $this->cuentaEgreso->id,
            'monto' => '200.00',
            'usuario_id' => $this->tesorero->id,
        ]);
        $corte2 = $this->corteService->solicitar($this->tesorero, $this->caja, '2026-02-28');
        $this->corteService->aprobar($this->admin, $corte2);

        // Continuidad: saldo_inicial corte 2 = saldo_final corte 1
        $this->assertSame((string) $corte1->saldo_final, (string) $corte2->saldo_inicial);
        $this->assertSame('1300.00', (string) $corte2->saldo_final);

        // Periodo sugerido para corte 3 debe iniciar el 2026-03-01
        $sugerido3 = $this->corteService->periodoSugerido($this->caja);
        $this->assertSame('2026-03-01', $sugerido3['inicio']->format('Y-m-d'));

        // Mes 3: Marzo
        Ingreso::factory()->create([
            'caja_id' => $this->caja->id,
            'fecha' => '2026-03-10',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '350.00',
            'usuario_id' => $this->tesorero->id,
        ]);
        $corte3 = $this->corteService->solicitar($this->tesorero, $this->caja, '2026-03-31');
        $this->corteService->aprobar($this->admin, $corte3);

        // Continuidad: saldo_inicial corte 3 = saldo_final corte 2
        $this->assertSame((string) $corte2->saldo_final, (string) $corte3->saldo_inicial);
        $this->assertSame('1650.00', (string) $corte3->saldo_final);
    }

    public function test_un_solo_corte_pendiente_por_caja(): void
    {
        $this->corteService->solicitar($this->tesorero, $this->caja, '2026-01-15');

        $this->expectException(ValidationException::class);
        $this->corteService->solicitar($this->tesorero, $this->caja, '2026-01-31');
    }

    public function test_fecha_fin_futura_es_rechazada(): void
    {
        $fechaFutura = Carbon::today()->addDays(5);

        $this->expectException(ValidationException::class);
        $this->corteService->solicitar($this->tesorero, $this->caja, $fechaFutura);
    }

    public function test_periodo_bloqueado_no_permite_crear_editar_ni_anular_movimientos(): void
    {
        // Crear un movimiento en enero
        $res = $this->movimientoService->crearIngreso($this->tesorero, [
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-15',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '100.00',
        ]);
        $ingreso = $res['ingreso'];

        // Solicitar corte que cubre todo enero
        $this->corteService->solicitar($this->tesorero, $this->caja, '2026-01-31');

        // Intento 1: Crear nuevo movimiento en el período bloqueado
        try {
            $this->movimientoService->crearEgreso($this->tesorero, [
                'caja_id' => $this->caja->id,
                'fecha' => '2026-01-20',
                'cuenta_egreso_id' => $this->cuentaEgreso->id,
                'monto' => '50.00',
                'descripcion' => 'Gasto bloqueado',
            ]);
            $this->fail('Se esperaba PeriodoBloqueadoException al crear');
        } catch (PeriodoBloqueadoException $e) {
            $this->assertTrue(true);
        }

        // Intento 2: Editar movimiento en período bloqueado
        try {
            $this->movimientoService->actualizar($this->tesorero, $ingreso, [
                'monto' => '200.00',
            ]);
            $this->fail('Se esperaba PeriodoBloqueadoException al editar');
        } catch (PeriodoBloqueadoException $e) {
            $this->assertTrue(true);
        }

        // Intento 3: Anular movimiento en período bloqueado
        try {
            $this->movimientoService->anular($this->tesorero, $ingreso, 'Motivo de prueba con más de 10 caracteres');
            $this->fail('Se esperaba PeriodoBloqueadoException al anular');
        } catch (PeriodoBloqueadoException $e) {
            $this->assertTrue(true);
        }
    }

    public function test_aprobar_y_rechazar_solo_por_admin(): void
    {
        $corte = $this->corteService->solicitar($this->tesorero, $this->caja, '2026-01-31');

        // Tesorero intenta aprobar -> 403 Forbidden
        $responseAprobar = $this->actingAs($this->tesorero)->post(route('cortes.aprobar', $corte));
        $responseAprobar->assertForbidden();

        // Tesorero intenta rechazar -> 403 Forbidden
        $responseRechazar = $this->actingAs($this->tesorero)->post(route('cortes.rechazar', $corte), [
            'observaciones' => 'Intento ilegal de rechazo',
        ]);
        $responseRechazar->assertForbidden();

        // Admin aprueba exitosamente
        $responseAdmin = $this->actingAs($this->admin)->post(route('cortes.aprobar', $corte));
        $responseAdmin->assertRedirect(route('cortes.show', $corte));
        $this->assertSame(EstadoCorte::Aprobado, $corte->fresh()->estado);
    }

    public function test_reabrir_solo_el_ultimo_corte_aprobado(): void
    {
        // Corte 1
        $corte1 = $this->corteService->solicitar($this->tesorero, $this->caja, '2026-01-15');
        $this->corteService->aprobar($this->admin, $corte1);

        // Corte 2
        $corte2 = $this->corteService->solicitar($this->tesorero, $this->caja, '2026-01-31');
        $this->corteService->aprobar($this->admin, $corte2);

        // Intentar reabrir el Corte 1 (que no es el último) debe lanzar TransicionInvalidaException
        $this->expectException(TransicionInvalidaException::class);
        $this->corteService->reabrir($this->admin, $corte1, 'Motivo de reapertura extenso y detallado');
    }

    public function test_tras_reabrir_el_periodo_vuelve_a_ser_editable_y_se_puede_solicitar_de_nuevo(): void
    {
        $res = $this->movimientoService->crearIngreso($this->tesorero, [
            'caja_id' => $this->caja->id,
            'fecha' => '2026-01-15',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '100.00',
        ]);
        $ingreso = $res['ingreso'];

        $corte = $this->corteService->solicitar($this->tesorero, $this->caja, '2026-01-31');
        $this->corteService->aprobar($this->admin, $corte);

        // Reabrir el corte
        $this->corteService->reabrir($this->admin, $corte, 'Se omitió registrar factura de servicios');
        $this->assertSame(EstadoCorte::Reabierto, $corte->fresh()->estado);

        // Ahora el período ya NO está bloqueado: podemos editar el movimiento
        $resAct = $this->movimientoService->actualizar($this->tesorero, $ingreso, [
            'monto' => '180.00',
        ]);
        $actualizado = $resAct['movimiento'];
        $this->assertSame('180.00', (string) $actualizado->monto);

        // Y podemos solicitar un nuevo corte para este período
        $nuevoCorte = $this->corteService->solicitar($this->tesorero, $this->caja, '2026-01-31', 'Corte corregido');
        $this->assertSame(EstadoCorte::Pendiente, $nuevoCorte->estado);
        $this->assertSame('1180.00', (string) $nuevoCorte->saldo_final);
    }

    public function test_notificaciones_enviadas_a_destinatarios_correctos(): void
    {
        Notification::fake();

        // 1. Solicitar corte -> Notifica a administradores activos
        $corte = $this->corteService->solicitar($this->tesorero, $this->caja, '2026-01-31');
        Notification::assertSentTo($this->admin, CorteSolicitadoNotification::class);

        // 2. Aprobar corte -> Notifica al solicitante
        $this->corteService->aprobar($this->admin, $corte);
        Notification::assertSentTo($this->tesorero, CorteAprobadoNotification::class);

        // 3. Reabrir corte -> Notifica al solicitante
        $this->corteService->reabrir($this->admin, $corte, 'Motivo de reapertura para pruebas');
        Notification::assertSentTo($this->tesorero, CorteReabiertoNotification::class);

        // 4. Solicitar de nuevo y rechazar -> Notifica rechazo al solicitante
        $corte2 = $this->corteService->solicitar($this->tesorero, $this->caja, '2026-01-31');
        $this->corteService->rechazar($this->admin, $corte2, 'Observación detallada de rechazo');
        Notification::assertSentTo($this->tesorero, CorteRechazadoNotification::class);
    }

    public function test_tesorero_no_ve_cortes_de_otra_caja_idor_403(): void
    {
        $corteAjeno = $this->corteService->solicitar($this->tesoreroAjeno, $this->cajaAjena, '2026-01-31');

        $response = $this->actingAs($this->tesorero)->get(route('cortes.show', $corteAjeno));
        $response->assertForbidden();
    }

    public function test_transiciones_invalidas_en_maquina_de_estados(): void
    {
        $corte = $this->corteService->solicitar($this->tesorero, $this->caja, '2026-01-31');

        // No se puede reabrir un corte pendiente
        try {
            $this->corteService->reabrir($this->admin, $corte, 'Motivo de reapertura con más de 10 caracteres');
            $this->fail('Se esperaba TransicionInvalidaException al reabrir corte pendiente');
        } catch (TransicionInvalidaException $e) {
            $this->assertTrue(true);
        }

        // Rechazar el corte pendiente
        $this->corteService->rechazar($this->admin, $corte, 'Observación válida de rechazo');

        // No se puede aprobar un corte rechazado
        try {
            $this->corteService->aprobar($this->admin, $corte);
            $this->fail('Se esperaba TransicionInvalidaException al aprobar corte rechazado');
        } catch (TransicionInvalidaException $e) {
            $this->assertTrue(true);
        }
    }

    public function test_descuadre_snapshot_en_aprobacion_lanza_excepcion(): void
    {
        $corte = $this->corteService->solicitar($this->tesorero, $this->caja, '2026-01-31');

        // Alteramos deliberadamente el snapshot en base de datos para simular manipulación
        $corte->saldo_final = '999999.00';
        $corte->save();

        $this->expectException(DescuadreSnapshotException::class);
        $this->corteService->aprobar($this->admin, $corte);
    }

    public function test_pantallas_de_cortes_renderizan_exitosamente(): void
    {
        $corte = $this->corteService->solicitar($this->tesorero, $this->caja, '2026-01-31');

        $this->actingAs($this->tesorero)
            ->get(route('cortes.index'))
            ->assertOk();

        $this->actingAs($this->tesorero)
            ->get(route('cortes.create', ['caja_id' => $this->caja->id]))
            ->assertOk();

        $this->actingAs($this->tesorero)
            ->get(route('cortes.show', $corte))
            ->assertOk();
    }
}
