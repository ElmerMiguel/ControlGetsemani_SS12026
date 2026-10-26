<?php

namespace Tests\Feature\Inventario;

use App\Enums\EstadoBien;
use App\Models\Bitacora;
use App\Models\Caja;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventarioTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $tesorero1;

    protected User $tesorero2;

    protected Departamento $depto1;

    protected Departamento $depto2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);

        // Crear usuarios
        $this->admin = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->admin->assignRole('admin');

        $this->tesorero1 = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->tesorero1->assignRole('tesorero');

        $this->tesorero2 = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->tesorero2->assignRole('tesorero');

        // Departamentos
        $this->depto1 = Departamento::factory()->create(['nombre' => 'Comité Juvenil', 'activo' => true]);
        $this->depto2 = Departamento::factory()->create(['nombre' => 'Consejo Local', 'activo' => true]);

        // Cajas
        $caja1 = Caja::factory()->create(['departamento_id' => $this->depto1->id]);
        $caja2 = Caja::factory()->create(['departamento_id' => $this->depto2->id]);

        // Asignaciones: Tesorero 1 en Depto 1, Tesorero 2 en Depto 2
        $this->tesorero1->cajas()->attach($caja1->id);
        $this->tesorero2->cajas()->attach($caja2->id);
    }

    public function test_admin_puede_ver_todos_los_bienes(): void
    {
        Inventario::factory()->create(['departamento_id' => $this->depto1->id, 'nombre' => 'Guitarra']);
        Inventario::factory()->create(['departamento_id' => $this->depto2->id, 'nombre' => 'Púlpito']);

        $response = $this->actingAs($this->admin)->get(route('inventario.index'));

        $response->assertStatus(200);
        $response->assertSee('Guitarra');
        $response->assertSee('Púlpito');
    }

    public function test_tesorero_solo_ve_bienes_de_sus_departamentos_asignados(): void
    {
        Inventario::factory()->create(['departamento_id' => $this->depto1->id, 'nombre' => 'Microfono Shure']);
        Inventario::factory()->create(['departamento_id' => $this->depto2->id, 'nombre' => 'Proyector Epson']);

        $response = $this->actingAs($this->tesorero1)->get(route('inventario.index'));

        $response->assertStatus(200);
        $response->assertSee('Microfono Shure');
        $response->assertDontSee('Proyector Epson');
    }

    public function test_admin_puede_registrar_bien(): void
    {
        $response = $this->actingAs($this->admin)->post(route('inventario.store'), [
            'departamento_id' => $this->depto1->id,
            'codigo' => 'ACT-JUV-001',
            'nombre' => 'Batería Electrónica',
            'descripcion' => 'Marca Roland con módulo TD-17',
            'valor_estimado' => '4500.00',
            'estado' => EstadoBien::Correcto->value,
            'fecha_registro' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('inventario.index'));
        $this->assertDatabaseHas('inventarios', [
            'codigo' => 'ACT-JUV-001',
            'nombre' => 'Batería Electrónica',
            'valor_estimado' => 4500.00,
            'estado' => 'correcto',
        ]);
    }

    public function test_tesorero_no_puede_crear_bien_en_departamento_ajeno_idor(): void
    {
        $response = $this->actingAs($this->tesorero1)->post(route('inventario.store'), [
            'departamento_id' => $this->depto2->id, // Pertenece a tesorero 2
            'codigo' => 'ACT-CON-001',
            'nombre' => 'Computadora Dell',
            'estado' => EstadoBien::Correcto->value,
            'fecha_registro' => now()->toDateString(),
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('inventarios', ['codigo' => 'ACT-CON-001']);
    }

    public function test_cambio_de_estado_queda_registrado_en_bitacora_con_motivo(): void
    {
        $bien = Inventario::factory()->create([
            'departamento_id' => $this->depto1->id,
            'codigo' => 'ACT-SON-005',
            'estado' => EstadoBien::Correcto,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('inventario.estado', $bien), [
            'estado' => EstadoBien::Danado->value,
            'motivo' => 'Cable interno roto durante el culto del domingo',
        ]);

        $response->assertRedirect(route('inventario.index'));
        $this->assertEquals(EstadoBien::Danado, $bien->fresh()->estado);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'tabla_afectada' => 'inventarios',
            'registro_id' => $bien->id,
            'accion' => 'editar',
        ]);

        $bitacora = Bitacora::where('registro_id', $bien->id)->latest('id')->first();
        $this->assertStringContainsString('Cable interno roto', $bitacora->descripcion);
    }

    public function test_dar_de_baja_aplica_soft_delete_y_registra_en_bitacora_rn3_01(): void
    {
        $bien = Inventario::factory()->create([
            'departamento_id' => $this->depto1->id,
            'codigo' => 'ACT-INS-099',
            'estado' => EstadoBien::Danado,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('inventario.destroy', $bien), [
            'motivo' => 'Daño irreparable en tarjeta madre',
        ]);

        $response->assertRedirect(route('inventario.index'));

        // El registro sigue en la BD pero con deleted_at (SoftDeletes)
        $this->assertSoftDeleted('inventarios', ['id' => $bien->id]);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'tabla_afectada' => 'inventarios',
            'registro_id' => $bien->id,
            'accion' => 'anular',
        ]);
    }

    public function test_tesorero_recibe_403_al_intentar_editar_bien_de_otro_departamento_idor(): void
    {
        $bienAjeno = Inventario::factory()->create([
            'departamento_id' => $this->depto2->id,
            'codigo' => 'ACT-DEPTO2-01',
        ]);

        $response = $this->actingAs($this->tesorero1)->get(route('inventario.edit', $bienAjeno));
        $response->assertForbidden();

        $responseUpdate = $this->actingAs($this->tesorero1)->put(route('inventario.update', $bienAjeno), [
            'departamento_id' => $this->depto2->id,
            'codigo' => 'ACT-DEPTO2-01',
            'nombre' => 'Nombre Modificado Ilegalmente',
            'fecha_registro' => now()->toDateString(),
        ]);
        $responseUpdate->assertForbidden();
    }
}
