<?php

namespace Tests\Feature;

use App\Enums\EstadoCorte;
use App\Enums\MedioCaja;
use App\Enums\TipoDepartamento;
use App\Models\Aportante;
use App\Models\Bitacora;
use App\Models\Caja;
use App\Models\CatalogoEgreso;
use App\Models\CatalogoIngreso;
use App\Models\CorteCaja;
use App\Models\Departamento;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\Transferencia;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\EstructuraSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EsquemaDatosTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalogos_y_estructura_seeders_se_ejecutan_correctamente(): void
    {
        $this->seed([CatalogosSeeder::class, EstructuraSeeder::class]);

        $this->assertDatabaseCount('catalogo_ingresos', 10);
        $this->assertDatabaseCount('catalogo_egresos', 37);
        $this->assertDatabaseCount('departamentos', 8);
        $this->assertDatabaseCount('cajas', 13);

        $transferenciaIngreso = CatalogoIngreso::where('codigo', '900')->first();
        $this->assertNotNull($transferenciaIngreso);
        $this->assertTrue($transferenciaIngreso->es_transferencia);

        $transferenciaEgreso = CatalogoEgreso::where('codigo', '900')->first();
        $this->assertNotNull($transferenciaEgreso);
        $this->assertTrue($transferenciaEgreso->es_transferencia);

        $cajaBanco = Caja::where('codigo', 'CAJA-CONST-03')->first();
        $this->assertNotNull($cajaBanco);
        $this->assertEquals(MedioCaja::Banco, $cajaBanco->medio);

        $diezmoConsejo = Caja::where('codigo', 'CAJA-CL-01')->first();
        $this->assertNotNull($diezmoConsejo);
        $this->assertEquals('16498.00', $diezmoConsejo->saldo_apertura);
    }

    public function test_factories_crean_registros_con_casts_apropiados(): void
    {
        $user = User::factory()->create();
        $depto = Departamento::factory()->create(['tipo' => TipoDepartamento::Consejo]);
        $caja = Caja::factory()->create(['departamento_id' => $depto->id, 'medio' => MedioCaja::Efectivo]);
        $aportante = Aportante::factory()->create();
        $catIngreso = CatalogoIngreso::factory()->create();
        $catEgreso = CatalogoEgreso::factory()->create();

        $ingreso = Ingreso::factory()->create([
            'caja_id' => $caja->id,
            'cuenta_ingreso_id' => $catIngreso->id,
            'aportante_id' => $aportante->id,
            'usuario_id' => $user->id,
            'monto' => 1250.50,
        ]);

        $egreso = Egreso::factory()->create([
            'caja_id' => $caja->id,
            'cuenta_egreso_id' => $catEgreso->id,
            'usuario_id' => $user->id,
            'monto' => 450.25,
        ]);

        $corte = CorteCaja::factory()->create([
            'caja_id' => $caja->id,
            'solicitado_por' => $user->id,
            'estado' => EstadoCorte::Pendiente,
            'saldo_inicial' => 1000.00,
            'total_ingresos' => 1250.50,
            'total_egresos' => 450.25,
            'saldo_final' => 1800.25,
        ]);

        $transferencia = Transferencia::factory()->create([
            'caja_origen_id' => $caja->id,
            'caja_destino_id' => $caja->id,
            'usuario_id' => $user->id,
            'monto' => 300.00,
        ]);

        $bitacora = Bitacora::factory()->create([
            'usuario_id' => $user->id,
            'accion' => 'test_creacion',
            'tabla_afectada' => 'cajas',
            'registro_id' => $caja->id,
        ]);

        $this->assertEquals('1250.50', $ingreso->monto);
        $this->assertEquals('450.25', $egreso->monto);
        $this->assertEquals('1800.25', $corte->saldo_final);
        $this->assertEquals('300.00', $transferencia->monto);
        $this->assertInstanceOf(EstadoCorte::class, $corte->estado);
        $this->assertNotNull($bitacora->fecha_hora);
    }

    public function test_ingresos_y_egresos_soportan_soft_deletes(): void
    {
        $ingreso = Ingreso::factory()->create();
        $egreso = Egreso::factory()->create();

        $ingreso->delete();
        $egreso->delete();

        $this->assertSoftDeleted('ingresos', ['id' => $ingreso->id]);
        $this->assertSoftDeleted('egresos', ['id' => $egreso->id]);
    }
}
