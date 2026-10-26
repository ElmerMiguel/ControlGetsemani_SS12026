<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreventBackHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);
    }

    public function test_rutas_autenticadas_incluyen_cabeceras_no_cache_para_evitar_historial_back(): void
    {
        $admin = User::factory()->create([
            'activo' => true,
            'must_change_password' => false,
        ]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $response->assertHeader('Pragma', 'no-cache');
    }
}
