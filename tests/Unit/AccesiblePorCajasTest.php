<?php

namespace Tests\Unit;

use App\Models\Caja;
use App\Models\Concerns\AccesiblePorCajas;
use App\Models\Departamento;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModeloPruebaCaja extends Model
{
    use AccesiblePorCajas;

    protected $table = 'ingresos';
}

class AccesiblePorCajasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);
    }

    public function test_administrador_no_es_filtrado_por_cajas(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $query = ModeloPruebaCaja::query()->accesiblesPara($admin);

        // Al ser admin, no debe agregar cláusula WHERE caja_id IN (...)
        $sql = $query->toSql();
        $this->assertStringNotContainsString('caja_id', $sql);
    }

    public function test_tesorero_es_filtrado_unicamente_a_sus_cajas_asignadas(): void
    {
        $depto = Departamento::factory()->create();
        $caja1 = Caja::factory()->create(['departamento_id' => $depto->id]);
        $caja2 = Caja::factory()->create(['departamento_id' => $depto->id]);

        $tesorero = User::factory()->create();
        $tesorero->assignRole('tesorero');
        $tesorero->cajas()->attach([$caja1->id, $caja2->id]);

        $query = ModeloPruebaCaja::query()->accesiblesPara($tesorero);

        $sql = $query->toSql();
        $this->assertStringContainsString('`ingresos`.`caja_id` in (?, ?)', $sql);
        $this->assertEqualsCanonicalizing([$caja1->id, $caja2->id], $query->getBindings());
    }

    public function test_tesorero_sin_cajas_asignadas_recibe_consulta_vacia(): void
    {
        $tesorero = User::factory()->create();
        $tesorero->assignRole('tesorero');

        $query = ModeloPruebaCaja::query()->accesiblesPara($tesorero);

        $this->assertStringContainsString('where 0 = 1', $query->toSql());
    }
}
