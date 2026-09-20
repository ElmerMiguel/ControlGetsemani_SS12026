<?php

namespace Tests\Feature\Bitacora;

use App\Models\Bitacora;
use App\Models\Caja;
use App\Models\CatalogoIngreso;
use App\Models\Departamento;
use App\Models\Ingreso;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Tests\TestCase;

class BitacoraTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);
    }

    public function test_crear_ingreso_escribe_en_bitacora(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $depto = Departamento::factory()->create();
        $caja = Caja::factory()->create(['departamento_id' => $depto->id]);
        $cuenta = CatalogoIngreso::factory()->create();

        $ingreso = Ingreso::create([
            'caja_id' => $caja->id,
            'fecha' => '2026-03-15',
            'cuenta_ingreso_id' => $cuenta->id,
            'monto' => '150.00',
            'recibo' => 'REC-001',
            'usuario_id' => $user->id,
        ]);

        $registro = Bitacora::where('tabla_afectada', 'ingresos')
            ->where('registro_id', $ingreso->id)
            ->where('accion', 'crear')
            ->first();

        $this->assertNotNull($registro);
        $this->assertEquals($user->id, $registro->usuario_id);
        $this->assertStringContainsString("Creó Ingreso #{$ingreso->id}", $registro->descripcion);
        $this->assertEquals('150.00', $registro->datos_despues['monto']);
    }

    public function test_editar_ingreso_escribe_en_bitacora_con_antes_y_despues(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $depto = Departamento::factory()->create();
        $caja = Caja::factory()->create(['departamento_id' => $depto->id]);
        $cuenta = CatalogoIngreso::factory()->create();

        $ingreso = Ingreso::create([
            'caja_id' => $caja->id,
            'fecha' => '2026-03-15',
            'cuenta_ingreso_id' => $cuenta->id,
            'monto' => '150.00',
            'recibo' => 'REC-001',
            'usuario_id' => $user->id,
        ]);

        // Modificamos el monto
        $ingreso->update(['monto' => '175.50']);

        $registro = Bitacora::where('tabla_afectada', 'ingresos')
            ->where('registro_id', $ingreso->id)
            ->where('accion', 'modificar')
            ->first();

        $this->assertNotNull($registro);
        $this->assertEquals('150.00', $registro->datos_antes['monto']);
        $this->assertEquals('175.50', $registro->datos_despues['monto']);
        $this->assertStringContainsString('monto 150.00 → 175.50', $registro->descripcion);
    }

    public function test_anular_ingreso_escribe_en_bitacora_como_anular(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $depto = Departamento::factory()->create();
        $caja = Caja::factory()->create(['departamento_id' => $depto->id]);
        $cuenta = CatalogoIngreso::factory()->create();

        $ingreso = Ingreso::create([
            'caja_id' => $caja->id,
            'fecha' => '2026-03-15',
            'cuenta_ingreso_id' => $cuenta->id,
            'monto' => '150.00',
            'recibo' => 'REC-001',
            'usuario_id' => $user->id,
        ]);

        $ingreso->delete();

        $registro = Bitacora::where('tabla_afectada', 'ingresos')
            ->where('registro_id', $ingreso->id)
            ->where('accion', 'anular')
            ->first();

        $this->assertNotNull($registro);
        $this->assertStringContainsString("Anuló Ingreso #{$ingreso->id}", $registro->descripcion);
    }

    public function test_editar_sin_cambios_no_escribe_en_bitacora(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $depto = Departamento::factory()->create();
        $caja = Caja::factory()->create(['departamento_id' => $depto->id]);
        $cuenta = CatalogoIngreso::factory()->create();

        $ingreso = Ingreso::create([
            'caja_id' => $caja->id,
            'fecha' => '2026-03-15',
            'cuenta_ingreso_id' => $cuenta->id,
            'monto' => '150.00',
            'recibo' => 'REC-001',
            'usuario_id' => $user->id,
        ]);

        // Conteo actual
        $conteoPrevio = Bitacora::count();

        // Guardar sin modificar ningún atributo
        $ingreso->save();

        $this->assertEquals($conteoPrevio, Bitacora::count());
    }

    public function test_password_no_aparece_en_datos_antes_ni_despues(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('ClaveAnterior123!'),
        ]);

        // Modificamos contraseña y nombre
        $user->update([
            'name' => 'Nombre Actualizado',
            'password' => Hash::make('NuevaClaveSuperSegura123!'),
        ]);

        $registro = Bitacora::where('tabla_afectada', 'users')
            ->where('registro_id', $user->id)
            ->where('accion', 'modificar')
            ->first();

        $this->assertNotNull($registro);
        $this->assertArrayNotHasKey('password', $registro->datos_antes ?? []);
        $this->assertArrayNotHasKey('password', $registro->datos_despues ?? []);
        $this->assertArrayNotHasKey('remember_token', $registro->datos_antes ?? []);
        $this->assertArrayNotHasKey('remember_token', $registro->datos_despues ?? []);
        $this->assertStringContainsString('cambió la contraseña', $registro->descripcion);
    }

    public function test_bitacora_no_se_puede_actualizar_ni_borrar(): void
    {
        $registro = Bitacora::create([
            'accion' => 'evento.prueba',
            'descripcion' => 'Prueba de inmutabilidad',
            'fecha_hora' => now(),
        ]);

        // Intento 1: update() lanza LogicException
        $this->expectException(LogicException::class);
        $registro->update(['descripcion' => 'Alterado']);
    }

    public function test_bitacora_no_se_puede_borrar(): void
    {
        $registro = Bitacora::create([
            'accion' => 'evento.prueba',
            'descripcion' => 'Prueba de inmutabilidad borrado',
            'fecha_hora' => now(),
        ]);

        // Intento: delete() lanza LogicException
        $this->expectException(LogicException::class);
        $registro->delete();
    }

    public function test_tesorero_recibe_403_en_bitacora(): void
    {
        $tesorero = User::factory()->create([
            'activo' => true,
            'must_change_password' => false,
        ]);
        $tesorero->assignRole('tesorero');

        $this->actingAs($tesorero);

        $this->get(route('bitacora.index'))->assertForbidden();

        $registro = Bitacora::create([
            'accion' => 'evento.prueba',
            'descripcion' => 'Prueba',
            'fecha_hora' => now(),
        ]);

        $this->get(route('bitacora.show', $registro))->assertForbidden();
    }

    public function test_login_logout_y_login_fallido_quedan_registrados(): void
    {
        $usuario = User::factory()->create([
            'activo' => true,
            'must_change_password' => false,
        ]);

        // 1. Evento Login
        event(new Login('web', $usuario, false));
        $logLogin = Bitacora::where('accion', 'auth.login')->where('usuario_id', $usuario->id)->first();
        $this->assertNotNull($logLogin);
        $this->assertStringContainsString('Inicio de sesión exitoso', $logLogin->descripcion);

        // 2. Evento Logout
        event(new Logout('web', $usuario));
        $logLogout = Bitacora::where('accion', 'auth.logout')->where('usuario_id', $usuario->id)->first();
        $this->assertNotNull($logLogout);
        $this->assertStringContainsString('Cierre de sesión', $logLogout->descripcion);

        // 3. Evento Failed (Login fallido)
        event(new Failed('web', null, ['email' => 'intento@getsemani.test', 'password' => 'Secreto12345!']));
        $logFailed = Bitacora::where('accion', 'auth.failed')->latest('id')->first();
        $this->assertNotNull($logFailed);
        $this->assertStringContainsString('intento@getsemani.test', $logFailed->descripcion);
        $this->assertStringNotContainsString('Secreto12345!', $logFailed->descripcion);
    }

    public function test_con_auditoria_activa_false_no_se_escribe_nada(): void
    {
        config(['auditoria.activa' => false]);

        $depto = Departamento::create([
            'nombre' => 'Departamento Sin Auditoría',
            'tipo' => 'comite',
            'activo' => true,
        ]);

        $registro = Bitacora::where('tabla_afectada', 'departamentos')
            ->where('registro_id', $depto->id)
            ->first();

        $this->assertNull($registro);
    }
}
