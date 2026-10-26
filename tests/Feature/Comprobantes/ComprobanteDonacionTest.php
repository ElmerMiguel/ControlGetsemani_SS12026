<?php

namespace Tests\Feature\Comprobantes;

use App\Models\Aportante;
use App\Models\Caja;
use App\Models\CatalogoIngreso;
use App\Models\ComprobanteDonacion;
use App\Models\Departamento;
use App\Models\Ingreso;
use App\Models\User;
use App\Notifications\ComprobanteDonacionNotification;
use App\Services\MovimientoService;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComprobanteDonacionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $tesorero1;

    protected User $tesorero2;

    protected Caja $caja1;

    protected Caja $caja2;

    protected CatalogoIngreso $cuenta;

    protected Aportante $aportante;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(RolesPermisosSeeder::class);

        $depto1 = Departamento::factory()->create(['nombre' => 'Comité Juvenil', 'activo' => true]);
        $depto2 = Departamento::factory()->create(['nombre' => 'Consejo Femenil', 'activo' => true]);

        $this->caja1 = Caja::factory()->create(['departamento_id' => $depto1->id, 'activa' => true]);
        $this->caja2 = Caja::factory()->create(['departamento_id' => $depto2->id, 'activa' => true]);

        $this->admin = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->admin->assignRole('admin');

        $this->tesorero1 = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->tesorero1->assignRole('tesorero');
        $this->tesorero1->cajas()->attach($this->caja1->id);

        $this->tesorero2 = User::factory()->create(['activo' => true, 'must_change_password' => false]);
        $this->tesorero2->assignRole('tesorero');
        $this->tesorero2->cajas()->attach($this->caja2->id);

        $this->cuenta = CatalogoIngreso::create([
            'codigo' => '4.1.01',
            'nombre' => 'Diezmos Generales',
            'activo' => true,
        ]);

        $this->aportante = Aportante::create([
            'nombre_completo' => 'Carlos Mendoza Pérez',
            'cui_dpi' => '2541890120101',
            'telefono' => '55554444',
            'correo' => 'carlos.mendoza@ejemplo.com',
            'activo' => true,
        ]);
    }

    public function test_generar_comprobante_exitosamente_para_ingreso_con_aportante(): void
    {
        $ingreso = Ingreso::create([
            'caja_id' => $this->caja1->id,
            'fecha' => now()->toDateString(),
            'cuenta_ingreso_id' => $this->cuenta->id,
            'monto' => '500.00',
            'aportante_id' => $this->aportante->id,
            'usuario_id' => $this->tesorero1->id,
        ]);

        $response = $this->actingAs($this->tesorero1)
            ->post(route('ingresos.comprobante.generar', $ingreso));

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('comprobantes_donacion', [
            'ingreso_id' => $ingreso->id,
            'aportante_id' => $this->aportante->id,
            'numero' => 'COMP-000001',
        ]);

        $comprobante = ComprobanteDonacion::where('ingreso_id', $ingreso->id)->first();
        Storage::disk('local')->assertExists($comprobante->ruta_pdf);
    }

    public function test_no_se_puede_generar_comprobante_para_ingreso_sin_aportante(): void
    {
        $ingreso = Ingreso::create([
            'caja_id' => $this->caja1->id,
            'fecha' => now()->toDateString(),
            'cuenta_ingreso_id' => $this->cuenta->id,
            'monto' => '250.00',
            'aportante_id' => null,
            'usuario_id' => $this->tesorero1->id,
        ]);

        $response = $this->actingAs($this->tesorero1)
            ->post(route('ingresos.comprobante.generar', $ingreso));

        $response->assertSessionHasErrors('error');
        $this->assertDatabaseMissing('comprobantes_donacion', [
            'ingreso_id' => $ingreso->id,
        ]);
    }

    public function test_no_se_puede_generar_comprobante_para_ingreso_anulado(): void
    {
        $ingreso = Ingreso::create([
            'caja_id' => $this->caja1->id,
            'fecha' => now()->toDateString(),
            'cuenta_ingreso_id' => $this->cuenta->id,
            'monto' => '300.00',
            'aportante_id' => $this->aportante->id,
            'usuario_id' => $this->tesorero1->id,
        ]);

        $ingreso->delete();

        $response = $this->actingAs($this->tesorero1)
            ->post(route('ingresos.comprobante.generar', $ingreso));

        // 404 or session error because route model binding excludes soft deleted by default
        $this->assertTrue(in_array($response->getStatusCode(), [404, 302]));
        $this->assertDatabaseMissing('comprobantes_donacion', [
            'ingreso_id' => $ingreso->id,
        ]);
    }

    public function test_comprobante_mantiene_correlativo_consecutivo_inmutable(): void
    {
        $ingreso1 = Ingreso::create([
            'caja_id' => $this->caja1->id,
            'fecha' => now()->toDateString(),
            'cuenta_ingreso_id' => $this->cuenta->id,
            'monto' => '100.00',
            'aportante_id' => $this->aportante->id,
            'usuario_id' => $this->tesorero1->id,
        ]);

        $ingreso2 = Ingreso::create([
            'caja_id' => $this->caja1->id,
            'fecha' => now()->toDateString(),
            'cuenta_ingreso_id' => $this->cuenta->id,
            'monto' => '200.00',
            'aportante_id' => $this->aportante->id,
            'usuario_id' => $this->tesorero1->id,
        ]);

        $this->actingAs($this->tesorero1)->post(route('ingresos.comprobante.generar', $ingreso1));
        $this->actingAs($this->tesorero1)->post(route('ingresos.comprobante.generar', $ingreso2));

        $this->assertDatabaseHas('comprobantes_donacion', ['ingreso_id' => $ingreso1->id, 'numero' => 'COMP-000001']);
        $this->assertDatabaseHas('comprobantes_donacion', ['ingreso_id' => $ingreso2->id, 'numero' => 'COMP-000002']);

        // Regenerar para ingreso1 no crea un tercer comprobante ni altera el correlativo
        $this->actingAs($this->tesorero1)->post(route('ingresos.comprobante.generar', $ingreso1));
        $this->assertEquals(2, ComprobanteDonacion::count());
    }

    public function test_descarga_pdf_retorna_binario_valido_con_cabeceras_correctas(): void
    {
        $ingreso = Ingreso::create([
            'caja_id' => $this->caja1->id,
            'fecha' => now()->toDateString(),
            'cuenta_ingreso_id' => $this->cuenta->id,
            'monto' => '450.00',
            'aportante_id' => $this->aportante->id,
            'usuario_id' => $this->tesorero1->id,
        ]);

        $this->actingAs($this->tesorero1)->post(route('ingresos.comprobante.generar', $ingreso));
        $comprobante = ComprobanteDonacion::where('ingreso_id', $ingreso->id)->first();

        $response = $this->actingAs($this->tesorero1)->get(route('comprobantes.descargar', $comprobante));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_comprobante_refleja_anulado_si_el_ingreso_es_anulado_posteriormente(): void
    {
        $ingreso = Ingreso::create([
            'caja_id' => $this->caja1->id,
            'fecha' => now()->toDateString(),
            'cuenta_ingreso_id' => $this->cuenta->id,
            'monto' => '750.00',
            'aportante_id' => $this->aportante->id,
            'usuario_id' => $this->tesorero1->id,
        ]);

        $this->actingAs($this->tesorero1)->post(route('ingresos.comprobante.generar', $ingreso));
        $comprobante = ComprobanteDonacion::where('ingreso_id', $ingreso->id)->first();

        // Anular ingreso vía MovimientoService
        app(MovimientoService::class)->anular($this->tesorero1, $ingreso, 'Error en digitación de comprobante original');

        $this->assertTrue($ingreso->fresh()->trashed());

        // Verificar que el PDF físico existe en almacenamiento
        Storage::disk('local')->assertExists($comprobante->ruta_pdf);

        // Verificar que la plantilla renderizada para el comprobante incluye la marca de agua y banner ANULADO
        $vistaRenderizada = view('comprobantes.pdf', [
            'comprobante' => $comprobante,
            'ingreso' => $ingreso->fresh(),
            'aportante' => $this->aportante,
        ])->render();

        $this->assertStringContainsString('ANULADO', $vistaRenderizada);
        $this->assertStringContainsString('DOCUMENTO ANULADO', $vistaRenderizada);
        $this->assertStringContainsString('Error en digitación de comprobante original', $vistaRenderizada);
    }

    public function test_tesorero_no_puede_generar_o_descargar_comprobante_de_caja_ajena_prevencion_idor(): void
    {
        $ingreso = Ingreso::create([
            'caja_id' => $this->caja1->id,
            'fecha' => now()->toDateString(),
            'cuenta_ingreso_id' => $this->cuenta->id,
            'monto' => '800.00',
            'aportante_id' => $this->aportante->id,
            'usuario_id' => $this->tesorero1->id,
        ]);

        // Tesorero2 (de caja2) no puede generar comprobante para ingreso de caja1
        $responseGenerar = $this->actingAs($this->tesorero2)
            ->post(route('ingresos.comprobante.generar', $ingreso));
        $responseGenerar->assertForbidden();

        // Tesorero1 genera el comprobante
        $this->actingAs($this->tesorero1)->post(route('ingresos.comprobante.generar', $ingreso));
        $comprobante = ComprobanteDonacion::where('ingreso_id', $ingreso->id)->first();

        // Tesorero2 no puede descargar el comprobante generado de caja1
        $responseDescargar = $this->actingAs($this->tesorero2)
            ->get(route('comprobantes.descargar', $comprobante));
        $responseDescargar->assertForbidden();
    }

    public function test_envio_comprobante_por_correo_notifica_y_actualiza_enviado_en(): void
    {
        Notification::fake();

        $ingreso = Ingreso::create([
            'caja_id' => $this->caja1->id,
            'fecha' => now()->toDateString(),
            'cuenta_ingreso_id' => $this->cuenta->id,
            'monto' => '350.00',
            'aportante_id' => $this->aportante->id,
            'usuario_id' => $this->tesorero1->id,
        ]);

        $this->actingAs($this->tesorero1)->post(route('ingresos.comprobante.generar', $ingreso));
        $comprobante = ComprobanteDonacion::where('ingreso_id', $ingreso->id)->first();

        $response = $this->actingAs($this->tesorero1)
            ->post(route('comprobantes.enviar', $comprobante), [
                'correo' => 'hermano.fiel@ejemplo.com',
            ]);

        $response->assertSessionHas('status');

        Notification::assertSentOnDemand(
            ComprobanteDonacionNotification::class,
            function (ComprobanteDonacionNotification $notification, array $channels, object $notifiable) {
                return $notifiable->routes['mail'] === 'hermano.fiel@ejemplo.com';
            }
        );

        $comprobante->refresh();
        $this->assertNotNull($comprobante->enviado_en);
    }
}
