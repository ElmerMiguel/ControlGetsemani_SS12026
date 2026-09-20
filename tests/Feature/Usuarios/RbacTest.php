<?php

namespace Tests\Feature\Usuarios;

use App\Models\Caja;
use App\Models\Departamento;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);
    }

    public function test_matriz_de_permisos_por_rol(): void
    {
        $adminRole = Role::findByName('admin');
        $tesoreroRole = Role::findByName('tesorero');

        // Admin debe tener todos los 21 permisos
        $this->assertCount(21, $adminRole->permissions);

        // Tesorero debe tener exactamente los 13 permisos operativos
        $permisosTesorero = [
            'catalogos.ver',
            'aportantes.gestionar',
            'ingresos.ver',
            'ingresos.crear',
            'ingresos.editar',
            'ingresos.anular',
            'egresos.ver',
            'egresos.crear',
            'egresos.editar',
            'egresos.anular',
            'cortes.solicitar',
            'reportes.ver',
            'reportes.exportar',
        ];

        $this->assertCount(13, $tesoreroRole->permissions);
        foreach ($permisosTesorero as $permiso) {
            $this->assertTrue($tesoreroRole->hasPermissionTo($permiso));
        }

        // Tesorero NO debe tener permisos de gestión administrativa
        $permisosProhibidos = [
            'usuarios.gestionar',
            'departamentos.gestionar',
            'cajas.gestionar',
            'catalogos.gestionar',
            'transferencias.crear',
            'cortes.aprobar',
            'cortes.reabrir',
            'bitacora.ver',
        ];

        foreach ($permisosProhibidos as $permiso) {
            $this->assertFalse($tesoreroRole->hasPermissionTo($permiso));
        }
    }

    public function test_tesorero_recibe_403_al_acceder_al_modulo_de_usuarios(): void
    {
        $tesorero = User::factory()->create([
            'activo' => true,
            'must_change_password' => false,
        ]);
        $tesorero->assignRole('tesorero');

        $this->actingAs($tesorero);

        $this->get(route('usuarios.index'))->assertForbidden();
        $this->get(route('usuarios.create'))->assertForbidden();
        $this->post(route('usuarios.store'), [])->assertForbidden();
        $this->get(route('usuarios.edit', $tesorero))->assertForbidden();
        $this->patch(route('usuarios.estado', $tesorero))->assertForbidden();
        $this->post(route('usuarios.password', $tesorero))->assertForbidden();
    }

    public function test_admin_crea_un_tesorero_con_cajas_y_ese_tesorero_solo_obtiene_sus_cajas(): void
    {
        $admin = User::factory()->create([
            'activo' => true,
            'must_change_password' => false,
        ]);
        $admin->assignRole('admin');

        $depto = Departamento::factory()->create();
        $caja1 = Caja::factory()->create(['departamento_id' => $depto->id]);
        $caja2 = Caja::factory()->create(['departamento_id' => $depto->id]);
        $caja3 = Caja::factory()->create(['departamento_id' => $depto->id]);

        $this->actingAs($admin);

        $response = $this->post(route('usuarios.store'), [
            'name' => 'Tesorero Pedro',
            'email' => 'pedro@getsemani.test',
            'rol' => 'tesorero',
            'cajas' => [$caja1->id, $caja2->id],
        ]);

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('status');
        $response->assertSessionHas('temp_password');

        $nuevoTesorero = User::where('email', 'pedro@getsemani.test')->first();
        $this->assertNotNull($nuevoTesorero);
        $this->assertTrue($nuevoTesorero->activo);
        $this->assertTrue($nuevoTesorero->must_change_password);
        $this->assertTrue($nuevoTesorero->hasRole('tesorero'));

        // Cajas asignadas
        $cajasAsignadas = $nuevoTesorero->cajas->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$caja1->id, $caja2->id], $cajasAsignadas);
        $this->assertNotContains($caja3->id, $cajasAsignadas);
    }

    public function test_tesorero_falla_si_no_se_le_asigna_ninguna_caja(): void
    {
        $admin = User::factory()->create([
            'activo' => true,
            'must_change_password' => false,
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin);

        $response = $this->post(route('usuarios.store'), [
            'name' => 'Tesorero Sin Cajas',
            'email' => 'sincajas@getsemani.test',
            'rol' => 'tesorero',
            'cajas' => [],
        ]);

        $response->assertSessionHasErrors('cajas');
    }

    public function test_no_se_puede_desactivar_a_si_mismo_ni_al_ultimo_admin_activo(): void
    {
        $admin = User::factory()->create([
            'activo' => true,
            'must_change_password' => false,
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin);

        // Intento 1: Desactivarse a sí mismo
        $responseSelf = $this->patch(route('usuarios.estado', $admin));
        $responseSelf->assertForbidden();
        $this->assertTrue($admin->fresh()->activo);

        // Crear un segundo admin
        $admin2 = User::factory()->create([
            'activo' => true,
            'must_change_password' => false,
        ]);
        $admin2->assignRole('admin');

        // Admin 1 puede desactivar al Admin 2 porque todavía queda Admin 1 activo
        $responseOtro = $this->patch(route('usuarios.estado', $admin2));
        $responseOtro->assertRedirect();
        $this->assertFalse($admin2->fresh()->activo);

        // Ahora solo queda 1 admin activo (Admin 1). Si cambiamos de sesión a Admin 2 (activado previamente)
        // o si intentamos desactivar al único admin restante:
        $admin2->refresh();
        $admin2->update(['activo' => true]);
        $this->actingAs($admin2);

        // Desactivamos a Admin 1
        $res = $this->patch(route('usuarios.estado', $admin));
        $res->assertRedirect();
        $this->assertFalse($admin->fresh()->activo);

        // Ahora Admin 2 es el ÚNICO admin activo en el sistema.
        // Si alguien intenta desactivar a Admin 2 (o él mismo):
        $this->actingAs($admin2);
        $responseUltimo = $this->patch(route('usuarios.estado', $admin2));
        $responseUltimo->assertForbidden();
        $this->assertTrue($admin2->fresh()->activo);
    }

    public function test_al_desactivar_usuario_la_siguiente_peticion_cierra_su_sesion(): void
    {
        $usuario = User::factory()->create([
            'activo' => true,
            'must_change_password' => false,
        ]);
        $usuario->assignRole('tesorero');

        $this->actingAs($usuario);

        // La primera petición responde 200 en dashboard
        $this->get(route('dashboard'))->assertOk();

        // El usuario es desactivado (por ejemplo por un administrador)
        $usuario->update(['activo' => false]);

        // La siguiente petición debe cerrar su sesión y redirigirlo a login con error
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_puede_regenerar_password_temporal(): void
    {
        $admin = User::factory()->create([
            'activo' => true,
            'must_change_password' => false,
        ]);
        $admin->assignRole('admin');

        $tesorero = User::factory()->create([
            'activo' => true,
            'must_change_password' => false,
            'password' => Hash::make('ClaveAntigua123!'),
        ]);
        $tesorero->assignRole('tesorero');

        $this->actingAs($admin);

        $response = $this->post(route('usuarios.password', $tesorero));
        $response->assertRedirect();
        $response->assertSessionHas('temp_password');

        $tempPassword = session('temp_password');
        $this->assertNotEmpty($tempPassword);

        $tesoreroActualizado = $tesorero->fresh();
        $this->assertTrue($tesoreroActualizado->must_change_password);
        $this->assertTrue(Hash::check($tempPassword, $tesoreroActualizado->password));
    }

    public function test_admin_puede_editar_usuario_conservando_su_mismo_correo(): void
    {
        $admin = User::factory()->create([
            'activo' => true,
            'must_change_password' => false,
        ]);
        $admin->assignRole('admin');

        $depto = Departamento::factory()->create();
        $caja = Caja::factory()->create(['departamento_id' => $depto->id]);

        $tesorero = User::factory()->create([
            'email' => 'tesorero.existente@getsemani.test',
            'name' => 'Nombre Original',
            'activo' => true,
            'must_change_password' => false,
        ]);
        $tesorero->assignRole('tesorero');
        $tesorero->cajas()->attach($caja->id);

        $this->actingAs($admin);

        // Editamos el nombre pero conservamos el mismo correo
        $response = $this->put(route('usuarios.update', $tesorero), [
            'name' => 'Nombre Modificado',
            'email' => 'tesorero.existente@getsemani.test',
            'rol' => 'tesorero',
            'cajas' => [$caja->id],
        ]);

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHasNoErrors();
        $this->assertEquals('Nombre Modificado', $tesorero->fresh()->name);
    }
}
