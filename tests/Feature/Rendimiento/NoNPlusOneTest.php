<?php

namespace Tests\Feature\Rendimiento;

use App\Models\Aportante;
use App\Models\Bitacora;
use App\Models\Caja;
use App\Models\CatalogoEgreso;
use App\Models\CatalogoIngreso;
use App\Models\CorteCaja;
use App\Models\Departamento;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NoNPlusOneTest
 *
 * Verifica que las pantallas principales del sistema cuenten con carga ansiosa (eager loading)
 * y no incurran en el problema de consultas N+1 ni violaciones de carga diferida
 * (LazyLoadingViolationException) con Model::preventLazyLoading(true) activo.
 */
class NoNPlusOneTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $tesorero;

    protected Caja $caja;

    protected function setUp(): void
    {
        parent::setUp();
        Model::preventLazyLoading(true);

        $this->seed(RolesPermisosSeeder::class);
        $this->seed(CatalogosSeeder::class);

        $this->admin = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->admin->assignRole('admin');

        $this->tesorero = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->tesorero->assignRole('tesorero');

        $depto = Departamento::create([
            'nombre' => 'Ministerio de Música',
            'tipo' => 'comite',
            'activo' => true,
        ]);

        $this->caja = Caja::create([
            'departamento_id' => $depto->id,
            'codigo' => 'CAJ-MUS',
            'nombre' => 'Caja Ministerio de Música',
            'saldo_apertura' => '2000.00',
            'fecha_apertura' => '2026-01-01',
            'activa' => true,
        ]);
        $this->caja->tesoreros()->attach($this->tesorero->id);

        $cuentaIngreso = CatalogoIngreso::where('es_transferencia', false)->first();
        $cuentaEgreso = CatalogoEgreso::where('es_transferencia', false)->first();

        $aportante = Aportante::create([
            'nombre_completo' => 'Hermano Juan Pérez',
            'cui_dpi' => '1234567890101',
            'activo' => true,
        ]);

        // Crear múltiples ingresos y egresos
        for ($i = 1; $i <= 5; $i++) {
            Ingreso::create([
                'caja_id' => $this->caja->id,
                'fecha' => '2026-01-10',
                'cuenta_ingreso_id' => $cuentaIngreso->id,
                'monto' => '100.00',
                'aportante_id' => $aportante->id,
                'usuario_id' => $this->tesorero->id,
            ]);

            Egreso::create([
                'caja_id' => $this->caja->id,
                'fecha' => '2026-01-15',
                'cuenta_egreso_id' => $cuentaEgreso->id,
                'monto' => '50.00',
                'descripcion' => 'Egreso de prueba '.$i,
                'usuario_id' => $this->tesorero->id,
            ]);
        }

        // Crear corte
        CorteCaja::create([
            'caja_id' => $this->caja->id,
            'periodo_inicio' => '2026-01-01',
            'periodo_fin' => '2026-01-31',
            'saldo_inicial' => '2000.00',
            'total_ingresos' => '500.00',
            'total_egresos' => '250.00',
            'saldo_final' => '2250.00',
            'estado' => 'pendiente',
            'solicitado_por' => $this->tesorero->id,
        ]);

        // Crear bitácora
        Bitacora::create([
            'usuario_id' => $this->admin->id,
            'accion' => 'test.registro',
            'descripcion' => 'Registro de prueba de auditoría',
            'ip' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test',
        ]);
    }

    public function test_pantalla_index_ingresos_no_incurre_en_lazy_loading(): void
    {
        $this->actingAs($this->admin)
            ->get(route('ingresos.index'))
            ->assertOk();

        $this->actingAs($this->tesorero)
            ->get(route('ingresos.index'))
            ->assertOk();
    }

    public function test_pantalla_index_egresos_no_incurre_en_lazy_loading(): void
    {
        $this->actingAs($this->admin)
            ->get(route('egresos.index'))
            ->assertOk();

        $this->actingAs($this->tesorero)
            ->get(route('egresos.index'))
            ->assertOk();
    }

    public function test_pantalla_index_cortes_no_incurre_en_lazy_loading(): void
    {
        $this->actingAs($this->admin)
            ->get(route('cortes.index'))
            ->assertOk();

        $this->actingAs($this->tesorero)
            ->get(route('cortes.index'))
            ->assertOk();
    }

    public function test_pantalla_index_cajas_no_incurre_en_lazy_loading(): void
    {
        $this->actingAs($this->admin)
            ->get(route('cajas.index'))
            ->assertOk();

        $this->actingAs($this->tesorero)
            ->get(route('cajas.index'))
            ->assertOk();
    }

    public function test_pantalla_index_bitacora_no_incurre_en_lazy_loading(): void
    {
        $this->actingAs($this->admin)
            ->get(route('bitacora.index'))
            ->assertOk();
    }

    public function test_pantalla_reportes_caja_no_incurre_en_lazy_loading(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reportes.caja'))
            ->assertOk();

        $this->actingAs($this->tesorero)
            ->get(route('reportes.caja', ['caja_id' => $this->caja->id]))
            ->assertOk();
    }
}
