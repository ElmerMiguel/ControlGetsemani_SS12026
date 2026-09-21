<?php

namespace Tests\Feature\Catalogos;

use App\Models\Aportante;
use App\Models\Caja;
use App\Models\CatalogoEgreso;
use App\Models\CatalogoIngreso;
use App\Models\Departamento;
use App\Models\Ingreso;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstructuraYCatalogosTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $tesorero;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);

        $this->admin = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->admin->assignRole('admin');

        $this->tesorero = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->tesorero->assignRole('tesorero');
    }

    public function test_admin_puede_crear_y_editar_departamento(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('departamentos.store'), [
            'nombre' => 'Consejo Pastoral',
            'tipo' => 'consejo',
            'descripcion' => 'Liderazgo pastoral',
        ]);

        $response->assertRedirect(route('departamentos.index'));
        $this->assertDatabaseHas('departamentos', [
            'nombre' => 'Consejo Pastoral',
            'tipo' => 'consejo',
            'activo' => true,
        ]);

        $depto = Departamento::where('nombre', 'Consejo Pastoral')->first();

        $updateResponse = $this->put(route('departamentos.update', $depto), [
            'nombre' => 'Consejo Pastoral Modificado',
            'tipo' => 'consejo',
            'descripcion' => 'Descripción actualizada',
        ]);

        $updateResponse->assertRedirect(route('departamentos.index'));
        $this->assertDatabaseHas('departamentos', [
            'id' => $depto->id,
            'nombre' => 'Consejo Pastoral Modificado',
        ]);
    }

    public function test_no_se_puede_desactivar_departamento_con_cajas_activas(): void
    {
        $this->actingAs($this->admin);

        $depto = Departamento::factory()->create(['activo' => true]);
        Caja::factory()->create([
            'departamento_id' => $depto->id,
            'activa' => true,
        ]);

        $response = $this->patch(route('departamentos.estado', $depto));

        // Debe ser denegado por la política según RN-13
        $response->assertForbidden();
        $this->assertTrue($depto->fresh()->activo);
    }

    public function test_unicidad_de_codigos_en_cajas_y_catalogos(): void
    {
        $this->actingAs($this->admin);

        $depto = Departamento::factory()->create();

        Caja::factory()->create(['codigo' => 'CJ-REPETIDA']);

        // Intentar registrar otra caja con el mismo código
        $responseCaja = $this->post(route('cajas.store'), [
            'departamento_id' => $depto->id,
            'nombre' => 'Nueva Caja Repetida',
            'codigo' => 'CJ-REPETIDA',
            'medio' => 'efectivo',
            'saldo_apertura' => '100.00',
            'fecha_apertura' => '2026-01-01',
        ]);

        $responseCaja->assertSessionHasErrors('codigo');

        // Unicidad en Catálogo Ingresos
        CatalogoIngreso::factory()->create(['codigo' => '050']);

        $responseIngreso = $this->post(route('catalogos.ingresos.store'), [
            'codigo' => '050',
            'nombre' => 'Ingreso Duplicado',
        ]);

        $responseIngreso->assertSessionHasErrors('codigo');

        // Unicidad en Catálogo Egresos
        CatalogoEgreso::factory()->create(['codigo' => '060']);

        $responseEgreso = $this->post(route('catalogos.egresos.store'), [
            'codigo' => '060',
            'nombre' => 'Egreso Duplicado',
        ]);

        $responseEgreso->assertSessionHasErrors('codigo');
    }

    public function test_inmutabilidad_de_saldo_y_fecha_apertura_en_caja_con_movimientos(): void
    {
        $this->actingAs($this->admin);

        $depto = Departamento::factory()->create();
        $caja = Caja::factory()->create([
            'departamento_id' => $depto->id,
            'saldo_apertura' => '500.00',
            'fecha_apertura' => '2026-01-01',
        ]);

        // Registrar un movimiento contable en la caja
        $cuenta = CatalogoIngreso::factory()->create();
        Ingreso::create([
            'caja_id' => $caja->id,
            'fecha' => '2026-01-02',
            'cuenta_ingreso_id' => $cuenta->id,
            'monto' => '100.00',
            'usuario_id' => $this->admin->id,
        ]);

        $this->assertTrue($caja->tieneMovimientos());

        // Intentar cambiar saldo_apertura y fecha_apertura
        $response = $this->put(route('cajas.update', $caja), [
            'departamento_id' => $depto->id,
            'nombre' => $caja->nombre,
            'codigo' => $caja->codigo,
            'medio' => $caja->medio->value,
            'saldo_apertura' => '1500.00',
            'fecha_apertura' => '2026-02-01',
        ]);

        $response->assertSessionHasErrors(['saldo_apertura', 'fecha_apertura']);

        $caja->refresh();
        $this->assertEquals('500.00', $caja->saldo_apertura);
        $this->assertEquals('2026-01-01', $caja->fecha_apertura->format('Y-m-d'));
    }

    public function test_caja_sin_movimientos_si_permite_modificar_saldo_y_fecha_apertura(): void
    {
        $this->actingAs($this->admin);

        $depto = Departamento::factory()->create();
        $caja = Caja::factory()->create([
            'departamento_id' => $depto->id,
            'saldo_apertura' => '500.00',
            'fecha_apertura' => '2026-01-01',
        ]);

        $this->assertFalse($caja->tieneMovimientos());

        $response = $this->put(route('cajas.update', $caja), [
            'departamento_id' => $depto->id,
            'nombre' => 'Caja Modificada',
            'codigo' => $caja->codigo,
            'medio' => $caja->medio->value,
            'saldo_apertura' => '800.00',
            'fecha_apertura' => '2026-01-15',
        ]);

        $response->assertRedirect(route('cajas.index'));

        $caja->refresh();
        $this->assertEquals('800.00', $caja->saldo_apertura);
        $this->assertEquals('2026-01-15', $caja->fecha_apertura->format('Y-m-d'));
    }

    public function test_cuentas_de_transferencia_900_no_son_editables_ni_desactivables(): void
    {
        $this->actingAs($this->admin);

        $cuentaTransferencia = CatalogoIngreso::factory()->create([
            'codigo' => '900',
            'nombre' => 'Transferencia Recibida',
            'es_transferencia' => true,
            'activo' => true,
        ]);

        // Intentar editar
        $updateResponse = $this->put(route('catalogos.ingresos.update', $cuentaTransferencia), [
            'codigo' => '901',
            'nombre' => 'Nombre Cambiado',
        ]);

        $updateResponse->assertForbidden();

        // Intentar cambiar estado
        $estadoResponse = $this->patch(route('catalogos.ingresos.estado', $cuentaTransferencia));
        $estadoResponse->assertForbidden();

        $cuentaTransferencia->refresh();
        $this->assertEquals('900', $cuentaTransferencia->codigo);
        $this->assertTrue($cuentaTransferencia->activo);
    }

    public function test_tesorero_ve_catalogos_en_solo_lectura_y_recibe_403_al_escribir(): void
    {
        $this->actingAs($this->tesorero);

        // Tesorero puede ver listados con catalogos.ver
        $this->get(route('catalogos.ingresos.index'))->assertOk();
        $this->get(route('catalogos.egresos.index'))->assertOk();

        // Tesorero no puede crear ni editar catálogos (requiere catalogos.gestionar)
        $this->get(route('catalogos.ingresos.create'))->assertForbidden();
        $this->post(route('catalogos.ingresos.store'), [
            'codigo' => '777',
            'nombre' => 'Cuenta No Permitida',
        ])->assertForbidden();

        $cuenta = CatalogoIngreso::factory()->create(['es_transferencia' => false]);
        $this->get(route('catalogos.ingresos.edit', $cuenta))->assertForbidden();
        $this->put(route('catalogos.ingresos.update', $cuenta), [
            'codigo' => '778',
            'nombre' => 'Modificación Denegada',
        ])->assertForbidden();
    }

    public function test_tesorero_solo_ve_sus_cajas_asignadas_en_index(): void
    {
        $cajaPropia = Caja::factory()->create(['nombre' => 'Caja Asignada']);
        $cajaAjena = Caja::factory()->create(['nombre' => 'Caja Confidencial']);

        $cajaPropia->tesoreros()->attach($this->tesorero->id);

        $this->actingAs($this->tesorero);

        $response = $this->get(route('cajas.index'));
        $response->assertOk();
        $response->assertSee('Caja Asignada');
        $response->assertDontSee('Caja Confidencial');
    }

    public function test_cui_de_aportante_se_muestra_enmascarado_con_ultimos_4_digitos(): void
    {
        $aportante = Aportante::factory()->create([
            'nombre_completo' => 'Hermano Test',
            'cui_dpi' => '2541890120101',
        ]);

        $this->assertEquals('•••• •••• • 0101', $aportante->cui_enmascarado);

        $this->actingAs($this->admin);
        $response = $this->get(route('aportantes.index'));
        $response->assertOk();
        $response->assertSee('•••• •••• • 0101');
        $response->assertDontSee('2541890120101');
    }

    public function test_bitacora_registra_cambios_en_departamentos_cajas_catalogos_y_aportantes(): void
    {
        $this->actingAs($this->admin);

        // 1. Bitácora en Departamento
        $depto = Departamento::create([
            'nombre' => 'Comité de Misiones',
            'tipo' => 'comite',
            'activo' => true,
        ]);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'tabla_afectada' => 'departamentos',
            'registro_id' => $depto->id,
            'accion' => 'crear',
        ]);

        // 2. Bitácora en Caja
        $caja = Caja::create([
            'departamento_id' => $depto->id,
            'nombre' => 'Caja Misiones',
            'codigo' => 'CJ-MIS-01',
            'medio' => 'efectivo',
            'saldo_apertura' => '200.00',
            'fecha_apertura' => '2026-01-01',
            'activa' => true,
        ]);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'tabla_afectada' => 'cajas',
            'registro_id' => $caja->id,
            'accion' => 'crear',
        ]);

        // 3. Bitácora en Aportante
        $aportante = Aportante::create([
            'nombre_completo' => 'Aportante Fiel',
            'cui_dpi' => '1234567890123',
            'activo' => true,
        ]);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'tabla_afectada' => 'aportantes',
            'registro_id' => $aportante->id,
            'accion' => 'crear',
        ]);
    }

    public function test_admin_puede_crear_y_actualizar_caja_con_tesoreros_asignados(): void
    {
        $this->actingAs($this->admin);

        $depto = Departamento::factory()->create();
        $tesorero2 = User::factory()->create(['activo' => true]);
        $tesorero2->assignRole('tesorero');

        $response = $this->post(route('cajas.store'), [
            'departamento_id' => $depto->id,
            'nombre' => 'Caja con Tesoreros',
            'codigo' => 'CJ-TES-01',
            'medio' => 'efectivo',
            'saldo_apertura' => '300.00',
            'fecha_apertura' => '2026-01-01',
            'activa' => '1',
            'tesoreros' => [$this->tesorero->id],
        ]);

        $response->assertRedirect(route('cajas.index'));

        $caja = Caja::where('codigo', 'CJ-TES-01')->first();
        $this->assertNotNull($caja);
        $this->assertTrue($caja->tesoreros->contains($this->tesorero->id));

        // Actualizar asignando a otro tesorero
        $updateResponse = $this->put(route('cajas.update', $caja), [
            'departamento_id' => $depto->id,
            'nombre' => 'Caja con Tesoreros Modificada',
            'codigo' => 'CJ-TES-01',
            'medio' => 'efectivo',
            'saldo_apertura' => '300.00',
            'fecha_apertura' => '2026-01-01',
            'activa' => '1',
            'tesoreros' => [$tesorero2->id],
        ]);

        $updateResponse->assertRedirect(route('cajas.index'));
        $caja->refresh();
        $this->assertFalse($caja->tesoreros->contains($this->tesorero->id));
        $this->assertTrue($caja->tesoreros->contains($tesorero2->id));
    }
}
