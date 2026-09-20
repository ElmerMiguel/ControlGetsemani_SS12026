<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_inactivo_no_puede_acceder_al_sistema(): void
    {
        $user = User::factory()->create([
            'activo' => false,
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_usuario_con_must_change_password_es_redirigido_a_pantalla_de_cambio(): void
    {
        $user = User::factory()->create([
            'activo' => true,
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('password.cambiar'));
    }

    public function test_usuario_puede_cambiar_su_password_temporal_exitosamente(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('ClaveAnterior1!'),
            'activo' => true,
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($user)->post('/cambiar-password', [
            'current_password' => 'ClaveAnterior1!',
            'password' => 'NuevaClaveSegura2026',
            'password_confirmation' => 'NuevaClaveSegura2026',
        ]);

        $response->assertRedirect(route('dashboard'));
        $user->refresh();

        $this->assertFalse((bool) $user->must_change_password);
        $this->assertTrue(Hash::check('NuevaClaveSegura2026', $user->password));
    }

    public function test_cambio_de_password_falla_si_no_cumple_regla_de_seguridad(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('ClaveAnterior1!'),
            'activo' => true,
            'must_change_password' => true,
        ]);

        // Menos de 10 caracteres o sin números
        $response = $this->actingAs($user)->post('/cambiar-password', [
            'current_password' => 'ClaveAnterior1!',
            'password' => 'corta1',
            'password_confirmation' => 'corta1',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertTrue((bool) $user->refresh()->must_change_password);
    }

    public function test_login_actualiza_last_login_at_del_usuario(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'last_login_at' => null,
            'activo' => true,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    }
}
