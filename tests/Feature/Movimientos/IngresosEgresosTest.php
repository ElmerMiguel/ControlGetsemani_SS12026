<?php

namespace Tests\Feature\Movimientos;

use App\Models\Caja;
use App\Models\CatalogoEgreso;
use App\Models\CatalogoIngreso;
use App\Models\CorteCaja;
use App\Models\Departamento;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngresosEgresosTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $tesorero;

    protected Caja $cajaTesorero;

    protected Caja $cajaAjena;

    protected CatalogoIngreso $cuentaIngreso;

    protected CatalogoEgreso $cuentaEgreso;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);

        $this->admin = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->admin->assignRole('admin');

        $this->tesorero = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->tesorero->assignRole('tesorero');

        $depto = Departamento::factory()->create();

        $this->cajaTesorero = Caja::factory()->create([
            'departamento_id' => $depto->id,
            'nombre' => 'Caja Tesorería',
            'saldo_apertura' => '500.00',
            'fecha_apertura' => '2026-01-01',
            'activa' => true,
        ]);
        $this->cajaTesorero->tesoreros()->attach($this->tesorero->id);

        $this->cajaAjena = Caja::factory()->create([
            'departamento_id' => $depto->id,
            'nombre' => 'Caja Ajena',
            'saldo_apertura' => '1000.00',
            'fecha_apertura' => '2026-01-01',
            'activa' => true,
        ]);

        $this->cuentaIngreso = CatalogoIngreso::factory()->create([
            'codigo' => '101',
            'nombre' => 'Diezmos Generales',
            'activo' => true,
            'es_transferencia' => false,
        ]);

        $this->cuentaEgreso = CatalogoEgreso::factory()->create([
            'codigo' => '201',
            'nombre' => 'Suministros Generales',
            'activo' => true,
            'es_transferencia' => false,
        ]);
    }

    public function test_crear_ingreso_y_egreso_exitosamente(): void
    {
        $this->actingAs($this->tesorero);

        // Crear Ingreso
        $responseIngreso = $this->post(route('ingresos.store'), [
            'caja_id' => $this->cajaTesorero->id,
            'fecha' => '2026-02-15',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '250.00',
            'recibo' => 'REC-100',
        ]);

        $responseIngreso->assertRedirect(route('ingresos.index'));
        $this->assertDatabaseHas('ingresos', [
            'caja_id' => $this->cajaTesorero->id,
            'monto' => '250.00',
            'recibo' => 'REC-100',
            'usuario_id' => $this->tesorero->id,
        ]);

        // Crear Egreso
        $responseEgreso = $this->post(route('egresos.store'), [
            'caja_id' => $this->cajaTesorero->id,
            'fecha' => '2026-02-16',
            'cuenta_egreso_id' => $this->cuentaEgreso->id,
            'monto' => '120.00',
            'descripcion' => 'Compra de bombillos y extensiones',
        ]);

        $responseEgreso->assertRedirect(route('egresos.index'));
        $this->assertDatabaseHas('egresos', [
            'caja_id' => $this->cajaTesorero->id,
            'monto' => '120.00',
            'descripcion' => 'Compra de bombillos y extensiones',
            'usuario_id' => $this->tesorero->id,
        ]);
    }

    public function test_monto_menor_o_igual_a_cero_es_rechazado(): void
    {
        $this->actingAs($this->tesorero);

        $responseCero = $this->post(route('ingresos.store'), [
            'caja_id' => $this->cajaTesorero->id,
            'fecha' => '2026-02-15',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '0.00',
        ]);
        $responseCero->assertSessionHasErrors('monto');

        $responseNegativo = $this->post(route('egresos.store'), [
            'caja_id' => $this->cajaTesorero->id,
            'fecha' => '2026-02-15',
            'cuenta_egreso_id' => $this->cuentaEgreso->id,
            'monto' => '-50.00',
            'descripcion' => 'Intento inválido',
        ]);
        $responseNegativo->assertSessionHasErrors('monto');
    }

    public function test_fecha_futura_es_rechazada(): void
    {
        $this->actingAs($this->tesorero);

        $fechaFutura = Carbon::now('America/Guatemala')->addDays(3)->toDateString();

        $response = $this->post(route('ingresos.store'), [
            'caja_id' => $this->cajaTesorero->id,
            'fecha' => $fechaFutura,
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '100.00',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('ingresos', [
            'caja_id' => $this->cajaTesorero->id,
            'fecha' => $fechaFutura,
        ]);
    }

    public function test_fecha_anterior_a_apertura_es_rechazada(): void
    {
        $this->actingAs($this->tesorero);

        // Apertura es 2026-01-01
        $fechaAnterior = '2025-12-31';

        $response = $this->post(route('egresos.store'), [
            'caja_id' => $this->cajaTesorero->id,
            'fecha' => $fechaAnterior,
            'cuenta_egreso_id' => $this->cuentaEgreso->id,
            'monto' => '100.00',
            'descripcion' => 'Gasto de año anterior',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('egresos', [
            'caja_id' => $this->cajaTesorero->id,
            'fecha' => $fechaAnterior,
        ]);
    }

    public function test_cuenta_inactiva_es_rechazada(): void
    {
        $this->actingAs($this->tesorero);

        $cuentaInactiva = CatalogoIngreso::factory()->create([
            'codigo' => '999',
            'activo' => false,
        ]);

        $response = $this->post(route('ingresos.store'), [
            'caja_id' => $this->cajaTesorero->id,
            'fecha' => '2026-02-15',
            'cuenta_ingreso_id' => $cuentaInactiva->id,
            'monto' => '100.00',
        ]);

        $response->assertSessionHasErrors('cuenta_ingreso_id');
    }

    public function test_caja_ajena_es_rechazada_para_tesorero(): void
    {
        $this->actingAs($this->tesorero);

        $response = $this->post(route('ingresos.store'), [
            'caja_id' => $this->cajaAjena->id,
            'fecha' => '2026-02-15',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '100.00',
        ]);

        $response->assertSessionHasErrors('caja_id');
    }

    public function test_anular_sin_motivo_o_con_menos_de_10_caracteres_es_rechazado(): void
    {
        $this->actingAs($this->tesorero);

        $ingreso = Ingreso::create([
            'caja_id' => $this->cajaTesorero->id,
            'fecha' => '2026-02-15',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '100.00',
            'usuario_id' => $this->tesorero->id,
        ]);

        // Sin motivo
        $responseVacio = $this->post(route('ingresos.anular', $ingreso), [
            'motivo' => '',
        ]);
        $responseVacio->assertSessionHasErrors('motivo');

        // Con menos de 10 caracteres
        $responseCorto = $this->post(route('ingresos.anular', $ingreso), [
            'motivo' => 'Error',
        ]);
        $responseCorto->assertSessionHasErrors('motivo');

        // Con motivo válido >= 10 caracteres
        $responseValido = $this->post(route('ingresos.anular', $ingreso), [
            'motivo' => 'Monto ingresado por equivocación de aportante',
        ]);
        $responseValido->assertSessionHas('success');

        $this->assertSoftDeleted('ingresos', ['id' => $ingreso->id]);
        $this->assertEquals($this->tesorero->id, $ingreso->fresh()->anulado_por);
        $this->assertEquals('Monto ingresado por equivocación de aportante', $ingreso->fresh()->motivo_anulacion);
    }

    public function test_movimiento_anulado_no_aparece_en_listas_ordinarias_ni_suma_al_total(): void
    {
        $ingresoValido = Ingreso::create([
            'caja_id' => $this->cajaTesorero->id,
            'fecha' => '2026-02-10',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '200.00',
            'recibo' => 'REC-VALIDO',
            'usuario_id' => $this->tesorero->id,
        ]);

        $ingresoAnulado = Ingreso::create([
            'caja_id' => $this->cajaTesorero->id,
            'fecha' => '2026-02-11',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '500.00',
            'recibo' => 'REC-ANULADO',
            'usuario_id' => $this->tesorero->id,
            'anulado_por' => $this->tesorero->id,
            'motivo_anulacion' => 'Anulación por error en recibo',
        ]);
        $ingresoAnulado->delete();

        $this->actingAs($this->tesorero);

        $response = $this->get(route('ingresos.index', ['caja_id' => $this->cajaTesorero->id]));
        $response->assertOk();
        $response->assertSee('REC-VALIDO');
        $response->assertDontSee('REC-ANULADO');

        // Total mostrado debe ser Q 200.00 y no incluir los 500.00
        $response->assertSee('Q 200.00');
        $response->assertDontSee('Q 700.00');
    }

    public function test_movimiento_en_periodo_bloqueado_no_se_crea_edita_ni_anula_corte_pendiente_o_aprobado(): void
    {
        // Crear un corte de caja pendiente que bloquea febrero 2026
        CorteCaja::factory()->create([
            'caja_id' => $this->cajaTesorero->id,
            'periodo_inicio' => '2026-02-01',
            'periodo_fin' => '2026-02-28',
            'estado' => 'pendiente',
        ]);

        $this->actingAs($this->admin);

        // 1. Intentar crear en periodo bloqueado
        $responseCrear = $this->post(route('ingresos.store'), [
            'caja_id' => $this->cajaTesorero->id,
            'fecha' => '2026-02-15',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '300.00',
        ]);
        $responseCrear->assertSessionHas('error');

        // 2. Crear un movimiento fuera del periodo bloqueado (ej. enero)
        $ingresoEnero = Ingreso::create([
            'caja_id' => $this->cajaTesorero->id,
            'fecha' => '2026-01-15',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '150.00',
            'usuario_id' => $this->admin->id,
        ]);

        // Intentar editar moviéndolo a una fecha bloqueada (febrero)
        $responseEditar = $this->put(route('ingresos.update', $ingresoEnero), [
            'caja_id' => $this->cajaTesorero->id,
            'fecha' => '2026-02-20',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '150.00',
        ]);
        $responseEditar->assertSessionHas('error');

        // 3. Crear movimiento directamente en BD dentro del periodo y verificar que ni el admin puede anularlo
        $ingresoBloqueado = Ingreso::create([
            'caja_id' => $this->cajaTesorero->id,
            'fecha' => '2026-02-10',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '80.00',
            'usuario_id' => $this->admin->id,
        ]);

        $responseAnular = $this->post(route('ingresos.anular', $ingresoBloqueado), [
            'motivo' => 'Intento de anulación en periodo cerrado',
        ]);

        // La policy o el servicio debe rechazarlo (403 o error en sesión)
        $this->assertTrue($responseAnular->isForbidden() || session()->has('error'));
        $this->assertNull($ingresoBloqueado->fresh()->deleted_at);
    }

    public function test_tesorero_no_puede_ver_ni_editar_movimientos_de_otra_caja_idor_403(): void
    {
        $ingresoAjeno = Ingreso::create([
            'caja_id' => $this->cajaAjena->id,
            'fecha' => '2026-02-10',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '1000.00',
            'usuario_id' => $this->admin->id,
        ]);

        $this->actingAs($this->tesorero);

        // Intento de editar vía IDOR
        $responseEdit = $this->get(route('ingresos.edit', $ingresoAjeno));
        $responseEdit->assertForbidden();

        $responseUpdate = $this->put(route('ingresos.update', $ingresoAjeno), [
            'caja_id' => $this->cajaAjena->id,
            'fecha' => '2026-02-10',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '100.00',
        ]);
        $responseUpdate->assertForbidden();

        // Intento de anular vía IDOR
        $responseAnular = $this->post(route('ingresos.anular', $ingresoAjeno), [
            'motivo' => 'Intento de anular caja que no me pertenece',
        ]);
        $responseAnular->assertForbidden();
    }

    public function test_egreso_que_deja_saldo_negativo_devuelve_advertencia_y_si_se_guarda(): void
    {
        // Saldo apertura es 500.00. Si se registra egreso de 800.00, queda en -300.00
        $this->actingAs($this->tesorero);

        $response = $this->post(route('egresos.store'), [
            'caja_id' => $this->cajaTesorero->id,
            'fecha' => '2026-02-15',
            'cuenta_egreso_id' => $this->cuentaEgreso->id,
            'monto' => '800.00',
            'descripcion' => 'Gasto de emergencia que excede el saldo de caja',
        ]);

        $response->assertRedirect(route('egresos.index'));
        $response->assertSessionHas('warning');

        $this->assertDatabaseHas('egresos', [
            'caja_id' => $this->cajaTesorero->id,
            'monto' => '800.00',
        ]);

        $this->assertEquals('-300.00', $this->cajaTesorero->calcularSaldoActual());
    }

    public function test_bitacora_registra_creacion_edicion_y_anulacion_de_movimientos(): void
    {
        $this->actingAs($this->admin);

        // 1. Crear
        $ingreso = Ingreso::create([
            'caja_id' => $this->cajaTesorero->id,
            'fecha' => '2026-01-20',
            'cuenta_ingreso_id' => $this->cuentaIngreso->id,
            'monto' => '400.00',
            'usuario_id' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'tabla_afectada' => 'ingresos',
            'registro_id' => $ingreso->id,
            'accion' => 'crear',
        ]);

        // 2. Editar
        $ingreso->update(['monto' => '450.00']);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'tabla_afectada' => 'ingresos',
            'registro_id' => $ingreso->id,
            'accion' => 'modificar',
        ]);

        // 3. Anular (delete con SoftDeletes)
        $ingreso->anulado_por = $this->admin->id;
        $ingreso->motivo_anulacion = 'Motivo de auditoría registrado';
        $ingreso->save();
        $ingreso->delete();

        $this->assertDatabaseHas('bitacora_auditoria', [
            'tabla_afectada' => 'ingresos',
            'registro_id' => $ingreso->id,
            'accion' => 'anular',
        ]);
    }
}
