<?php

namespace Tests\Feature\Seguridad;

use App\Models\Caja;
use App\Models\CorteCaja;
use App\Models\Departamento;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * MatrizAutorizacionRutasTest
 *
 * Implementa la auditoría y verificación exhaustiva de la matriz de autorización:
 * - Lista blanca estricta para rutas públicas.
 * - Toda ruta protegida exige autenticación (invitado -> 302 a login / 401).
 * - Las rutas administrativas rechazan a tesoreros con 403 Forbidden.
 * - Protección IDOR en rutas de caja para tesoreros sin asignación.
 * - El administrador cuenta con acceso autorizado a todas las pantallas clave.
 */
class MatrizAutorizacionRutasTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $tesorero;

    protected User $tesoreroAjeno;

    protected Caja $caja;

    protected Caja $cajaAjena;

    protected CorteCaja $corteAjeno;

    protected array $rutasPublicasPermitidas = [
        '/',
        'login',
        'password.request',
        'password.email',
        'password.reset',
        'password.store',
        'up',
        'storage.local',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);
        $this->seed(CatalogosSeeder::class);

        $this->admin = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->admin->assignRole('admin');

        $this->tesorero = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->tesorero->assignRole('tesorero');

        $this->tesoreroAjeno = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->tesoreroAjeno->assignRole('tesorero');

        $depto = Departamento::create([
            'nombre' => 'Departamento General',
            'tipo' => 'consejo',
            'activo' => true,
        ]);

        $this->caja = Caja::create([
            'departamento_id' => $depto->id,
            'codigo' => 'CAJ-MATRIZ-1',
            'nombre' => 'Caja Asignada',
            'saldo_apertura' => '1000.00',
            'fecha_apertura' => '2026-01-01',
            'activa' => true,
        ]);
        $this->caja->tesoreros()->attach($this->tesorero->id);

        $this->cajaAjena = Caja::create([
            'departamento_id' => $depto->id,
            'codigo' => 'CAJ-MATRIZ-2',
            'nombre' => 'Caja Ajena',
            'saldo_apertura' => '1000.00',
            'fecha_apertura' => '2026-01-01',
            'activa' => true,
        ]);
        $this->cajaAjena->tesoreros()->attach($this->tesoreroAjeno->id);

        $this->corteAjeno = CorteCaja::create([
            'caja_id' => $this->cajaAjena->id,
            'periodo_inicio' => '2026-01-01',
            'periodo_fin' => '2026-01-31',
            'saldo_inicial' => '1000.00',
            'total_ingresos' => '0.00',
            'total_egresos' => '0.00',
            'saldo_final' => '1000.00',
            'estado' => 'pendiente',
            'solicitado_por' => $this->tesoreroAjeno->id,
        ]);
    }

    /**
     * Valida que no existan rutas expuestas sin autenticación salvo la lista blanca explícita.
     */
    public function test_todas_las_rutas_no_publicas_exigen_autenticacion(): void
    {
        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            $uri = $route->uri();
            $methods = $route->methods();
            $routeName = $route->getName();

            // Ignorar rutas de framework o depuración
            if (str_starts_with($uri, '_debugbar') || str_starts_with($uri, '_ignition') || str_starts_with($uri, 'sanctum') || str_starts_with($uri, 'storage/')) {
                continue;
            }

            // Si es ruta pública en lista blanca, no debe requerir auth
            if (in_array($routeName, $this->rutasPublicasPermitidas) || in_array($uri, $this->rutasPublicasPermitidas)) {
                continue;
            }

            $middlewares = $route->gatherMiddleware();

            $this->assertTrue(
                in_array('auth', $middlewares),
                "Vulnerabilidad de seguridad detectada: La ruta [{$uri}] (nombre: {$routeName}) no cuenta con middleware 'auth' y no está en la lista blanca de rutas públicas permitidas."
            );
        }
    }

    #[DataProvider('rutasAdministrativasProvider')]
    public function test_tesorero_recibe_403_en_rutas_administrativas(string $method, string $uri): void
    {
        $response = $this->actingAs($this->tesorero)->call($method, $uri);

        $this->assertEquals(
            403,
            $response->status(),
            "Se esperaba 403 Forbidden para tesorero en [{$method} {$uri}], pero se obtuvo [{$response->status()}]."
        );
    }

    public static function rutasAdministrativasProvider(): array
    {
        return [
            ['GET', '/usuarios'],
            ['GET', '/usuarios/create'],
            ['POST', '/usuarios'],
            ['GET', '/bitacora'],
            ['GET', '/departamentos/create'],
            ['POST', '/departamentos'],
            ['GET', '/cajas/create'],
            ['POST', '/cajas'],
            ['GET', '/catalogos/ingresos/create'],
            ['POST', '/catalogos/ingresos'],
            ['GET', '/catalogos/egresos/create'],
            ['POST', '/catalogos/egresos'],
        ];
    }

    public function test_tesorero_recibe_403_al_intentar_aprobar_rechazar_o_reabrir_cortes(): void
    {
        // Tesorero intentando aprobar
        $this->actingAs($this->tesorero)
            ->post(route('cortes.aprobar', $this->corteAjeno))
            ->assertForbidden();

        // Tesorero intentando rechazar
        $this->actingAs($this->tesorero)
            ->post(route('cortes.rechazar', $this->corteAjeno), ['observaciones' => 'Rechazo no autorizado'])
            ->assertForbidden();

        // Tesorero intentando reabrir
        $this->actingAs($this->tesorero)
            ->post(route('cortes.reabrir', $this->corteAjeno), ['motivo' => 'Reapertura no autorizada'])
            ->assertForbidden();
    }

    public function test_tesorero_recibe_403_en_caja_ajena_idor(): void
    {
        // Ver detalle de caja ajena
        $this->actingAs($this->tesorero)
            ->get(route('cajas.show', $this->cajaAjena))
            ->assertForbidden();

        // Ver corte de caja ajena
        $this->actingAs($this->tesorero)
            ->get(route('cortes.show', $this->corteAjeno))
            ->assertForbidden();

        // Ver reporte de caja ajena
        $this->actingAs($this->tesorero)
            ->get(route('reportes.caja', ['caja_id' => $this->cajaAjena->id]))
            ->assertForbidden();

        // Exportar PDF de caja ajena
        $this->actingAs($this->tesorero)
            ->get(route('reportes.caja.pdf', ['caja_id' => $this->cajaAjena->id]))
            ->assertForbidden();

        // Exportar Excel de caja ajena
        $this->actingAs($this->tesorero)
            ->get(route('reportes.caja.xlsx', ['caja_id' => $this->cajaAjena->id]))
            ->assertForbidden();
    }

    public function test_admin_accede_exitosamente_a_todas_las_pantallas_principales(): void
    {
        $rutas = [
            route('dashboard'),
            route('cajas.index'),
            route('cajas.show', $this->caja),
            route('cortes.index'),
            route('cortes.show', $this->corteAjeno),
            route('reportes.caja', ['caja_id' => $this->caja->id]),
            route('usuarios.index'),
            route('bitacora.index'),
            route('departamentos.index'),
            route('catalogos.ingresos.index'),
            route('catalogos.egresos.index'),
            route('notificaciones.index'),
        ];

        foreach ($rutas as $url) {
            $response = $this->actingAs($this->admin)->get($url);
            $this->assertEquals(
                200,
                $response->status(),
                "Admin no pudo acceder a [{$url}]. Código recibido: {$response->status()}"
            );
        }
    }
}
