<?php

namespace Tests\Feature\Movimientos;

use App\Exceptions\MovimientoInvalidoException;
use App\Exceptions\PeriodoBloqueadoException;
use App\Models\Caja;
use App\Models\CorteCaja;
use App\Models\Departamento;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\User;
use App\Services\TransferenciaService;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferenciaTest extends TestCase
{
    use RefreshDatabase;

    protected TransferenciaService $transferenciaService;

    protected User $admin;

    protected User $tesoreroOrigen;

    protected User $tesoreroAjeno;

    protected Caja $cajaOrigen;

    protected Caja $cajaDestino;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);
        $this->seed(CatalogosSeeder::class);

        $this->transferenciaService = app(TransferenciaService::class);

        $this->admin = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->admin->assignRole('admin');

        $this->tesoreroOrigen = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->tesoreroOrigen->assignRole('tesorero');

        $this->tesoreroAjeno = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->tesoreroAjeno->assignRole('tesorero');

        $depto = Departamento::create([
            'nombre' => 'Ministerios Generales',
            'tipo' => 'consejo',
            'activo' => true,
        ]);

        $this->cajaOrigen = Caja::create([
            'departamento_id' => $depto->id,
            'codigo' => 'CAJ-ORIGEN',
            'nombre' => 'Caja Origen',
            'saldo_apertura' => '5000.00',
            'fecha_apertura' => '2026-01-01',
            'activa' => true,
        ]);
        $this->cajaOrigen->tesoreros()->attach($this->tesoreroOrigen->id);

        $this->cajaDestino = Caja::create([
            'departamento_id' => $depto->id,
            'codigo' => 'CAJ-DESTINO',
            'nombre' => 'Caja Destino',
            'saldo_apertura' => '1000.00',
            'fecha_apertura' => '2026-01-01',
            'activa' => true,
        ]);
    }

    public function test_crear_transferencia_genera_dos_movimientos_atomicos_vinculados(): void
    {
        $fecha = Carbon::now('America/Guatemala')->format('Y-m-d');

        $transferencia = $this->transferenciaService->crear($this->tesoreroOrigen, [
            'caja_origen_id' => $this->cajaOrigen->id,
            'caja_destino_id' => $this->cajaDestino->id,
            'fecha' => $fecha,
            'monto' => '750.00',
            'concepto' => 'Diezmo de diezmo a Junta',
        ]);

        $this->assertDatabaseHas('transferencias', [
            'id' => $transferencia->id,
            'caja_origen_id' => $this->cajaOrigen->id,
            'caja_destino_id' => $this->cajaDestino->id,
            'monto' => '750.00',
        ]);

        // Verifica egreso en origen
        $egreso = Egreso::where('transferencia_id', $transferencia->id)->first();
        $this->assertNotNull($egreso);
        $this->assertEquals($this->cajaOrigen->id, $egreso->caja_id);
        $this->assertEquals('750.00', $egreso->monto);
        $this->assertTrue($egreso->cuenta->es_transferencia);

        // Verifica ingreso en destino
        $ingreso = Ingreso::where('transferencia_id', $transferencia->id)->first();
        $this->assertNotNull($ingreso);
        $this->assertEquals($this->cajaDestino->id, $ingreso->caja_id);
        $this->assertEquals('750.00', $ingreso->monto);
        $this->assertTrue($ingreso->cuenta->es_transferencia);

        // Bitácora registrada
        $this->assertDatabaseHas('bitacora_auditoria', [
            'accion' => 'transferencia.creada',
        ]);
    }

    public function test_transferencia_falla_si_caja_origen_es_igual_a_destino(): void
    {
        $fecha = Carbon::now('America/Guatemala')->format('Y-m-d');

        $this->expectException(MovimientoInvalidoException::class);
        $this->expectExceptionMessage('La caja de origen y destino deben ser distintas.');

        $this->transferenciaService->crear($this->admin, [
            'caja_origen_id' => $this->cajaOrigen->id,
            'caja_destino_id' => $this->cajaOrigen->id,
            'fecha' => $fecha,
            'monto' => '100.00',
            'concepto' => 'Transferencia inválida misma caja',
        ]);
    }

    public function test_anular_transferencia_anula_simultaneamente_ambos_movimientos(): void
    {
        $fecha = Carbon::now('America/Guatemala')->format('Y-m-d');

        $transferencia = $this->transferenciaService->crear($this->admin, [
            'caja_origen_id' => $this->cajaOrigen->id,
            'caja_destino_id' => $this->cajaDestino->id,
            'fecha' => $fecha,
            'monto' => '300.00',
            'concepto' => 'Transferencia temporal para anulación',
        ]);

        $this->transferenciaService->anular(
            $this->admin,
            $transferencia,
            'Error en el monto digitado originalmente'
        );

        // Transferencia eliminada con SoftDeletes
        $this->assertSoftDeleted('transferencias', ['id' => $transferencia->id]);
        $this->assertEquals('Error en el monto digitado originalmente', $transferencia->fresh()->motivo_anulacion);

        // Egreso e ingreso eliminados con SoftDeletes
        $this->assertSoftDeleted('egresos', ['transferencia_id' => $transferencia->id]);
        $this->assertSoftDeleted('ingresos', ['transferencia_id' => $transferencia->id]);

        $this->assertDatabaseHas('bitacora_auditoria', [
            'accion' => 'transferencia.anulada',
        ]);
    }

    public function test_transferencia_rechazada_si_periodo_esta_bloqueado_por_corte(): void
    {
        // Bloquear caja origen con un corte aprobado
        CorteCaja::create([
            'caja_id' => $this->cajaOrigen->id,
            'periodo_inicio' => '2026-01-01',
            'periodo_fin' => '2026-01-31',
            'saldo_inicial' => '5000.00',
            'total_ingresos' => '0.00',
            'total_egresos' => '0.00',
            'saldo_final' => '5000.00',
            'estado' => 'aprobado',
            'solicitado_por' => $this->admin->id,
        ]);

        $this->expectException(PeriodoBloqueadoException::class);

        $this->transferenciaService->crear($this->admin, [
            'caja_origen_id' => $this->cajaOrigen->id,
            'caja_destino_id' => $this->cajaDestino->id,
            'fecha' => '2026-01-15',
            'monto' => '500.00',
            'concepto' => 'Transferencia en periodo bloqueado',
        ]);
    }

    public function test_tesorero_sin_acceso_a_caja_origen_es_rechazado_idor(): void
    {
        $fecha = Carbon::now('America/Guatemala')->format('Y-m-d');

        $this->expectException(MovimientoInvalidoException::class);
        $this->expectExceptionMessage('No tiene autorización para transferir fondos desde la caja de origen.');

        $this->transferenciaService->crear($this->tesoreroAjeno, [
            'caja_origen_id' => $this->cajaOrigen->id,
            'caja_destino_id' => $this->cajaDestino->id,
            'fecha' => $fecha,
            'monto' => '200.00',
            'concepto' => 'Intento IDOR tesorero ajeno',
        ]);
    }
}
