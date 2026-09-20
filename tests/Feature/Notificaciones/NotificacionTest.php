<?php

namespace Tests\Feature\Notificaciones;

use App\Models\Caja;
use App\Models\Departamento;
use App\Models\User;
use App\Services\CorteCajaService;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificacionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $tesorero;

    protected Caja $caja;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);

        $this->admin = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->admin->assignRole('admin');

        $this->tesorero = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->tesorero->assignRole('tesorero');

        $depto = Departamento::factory()->create();
        $this->caja = Caja::factory()->create([
            'departamento_id' => $depto->id,
            'nombre' => 'Caja Notif',
            'saldo_apertura' => '1000.00',
            'fecha_apertura' => '2026-01-01',
            'activa' => true,
        ]);
        $this->caja->tesoreros()->attach($this->tesorero->id);
    }

    public function test_admin_puede_ver_y_marcar_notificaciones_como_leidas(): void
    {
        $corte = app(CorteCajaService::class)->solicitar($this->tesorero, $this->caja, '2026-01-31');

        $this->assertCount(1, $this->admin->unreadNotifications);

        // Ver pantalla de notificaciones
        $response = $this->actingAs($this->admin)->get(route('notificaciones.index'));
        $response->assertOk();
        $response->assertSee('Corte de Caja Notif');

        // Marcar individual como leída
        $notif = $this->admin->unreadNotifications->first();
        $responseLeer = $this->actingAs($this->admin)->post(route('notificaciones.leer', $notif->id));
        $responseLeer->assertRedirect(route('cortes.show', $corte));

        $this->assertCount(0, $this->admin->fresh()->unreadNotifications);
    }

    public function test_marcar_todas_las_notificaciones_como_leidas(): void
    {
        $corte = app(CorteCajaService::class)->solicitar($this->tesorero, $this->caja, '2026-01-31');

        $this->assertGreaterThan(0, $this->admin->unreadNotifications()->count());

        $response = $this->actingAs($this->admin)->post(route('notificaciones.leer-todas'));
        $response->assertSessionHas('success');

        $this->assertSame(0, $this->admin->fresh()->unreadNotifications()->count());
    }
}
