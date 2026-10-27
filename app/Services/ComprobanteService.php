<?php

namespace App\Services;

use App\Models\ComprobanteDonacion;
use App\Models\Ingreso;
use App\Models\User;
use App\Notifications\ComprobanteDonacionNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Service ComprobanteService
 *
 * Implementa las reglas de negocio RF-07, RF-14 y RN3-02:
 * - Emisión formal de comprobantes de donación en PDF para ingresos con aportante identificado.
 * - Numeración correlativa consecutiva e inmutable (COMP-XXXXXX).
 * - Prohibición de emitir comprobantes a ingresos sin aportante o anulados.
 * - Reflejo indeleble de marca de agua y cintillo de ANULADO si el ingreso fue anulado con posterioridad.
 * - Despacho de notificación por correo electrónico institucional con comprobante adjunto.
 */
class ComprobanteService
{
    public function __construct(
        protected BitacoraService $bitacoraService
    ) {}

    /**
     * Genera un comprobante de donación formal para un ingreso con aportante.
     *
     * @throws DomainException
     */
    public function generar(Ingreso $ingreso, User $generador): ComprobanteDonacion
    {
        // RN3-02: Requiere aportante identificado
        if (! $ingreso->aportante_id) {
            throw new DomainException('No se puede generar comprobante para un ingreso sin aportante identificado.');
        }

        // RN3-02: No se permite generar comprobante a un ingreso anulado
        if ($ingreso->trashed()) {
            throw new DomainException('No se puede generar comprobante para un ingreso anulado.');
        }

        return DB::transaction(function () use ($ingreso, $generador) {
            // Verificar si ya existe comprobante para este ingreso
            $comprobante = ComprobanteDonacion::where('ingreso_id', $ingreso->id)->first();

            if (! $comprobante) {
                // Obtener el siguiente correlativo atómicamente a partir del último comprobante registrado
                $ultimoComprobante = ComprobanteDonacion::lockForUpdate()->orderByDesc('id')->first();
                $siguienteSecuencia = 1;

                if ($ultimoComprobante && preg_match('/COMP-(\d+)/', $ultimoComprobante->numero, $coincidencias)) {
                    $siguienteSecuencia = ((int) $coincidencias[1]) + 1;
                }

                $siguienteNumero = sprintf('COMP-%06d', $siguienteSecuencia);

                $comprobante = new ComprobanteDonacion;
                $comprobante->ingreso_id = $ingreso->id;
                $comprobante->aportante_id = $ingreso->aportante_id;
                $comprobante->numero = $siguienteNumero;
                $comprobante->generado_en = now();
                $comprobante->generado_por = $generador->id;
                $comprobante->ruta_pdf = "comprobantes/{$siguienteNumero}.pdf";
                $comprobante->save();

                $this->bitacoraService->registrar(
                    'crear',
                    $comprobante,
                    "Generación de comprobante {$comprobante->numero} para ingreso #{$ingreso->id}",
                    null,
                    $comprobante->toArray(),
                    $generador->id
                );
            }

            // Renderizar y guardar el PDF en almacenamiento local privado
            $this->regenerarPdf($comprobante);

            return $comprobante;
        });
    }

    /**
     * Renderiza el archivo PDF del comprobante y lo almacena en disco.
     */
    public function regenerarPdf(ComprobanteDonacion $comprobante): string
    {
        $ingreso = $comprobante->ingreso;
        $ingreso->loadMissing(['caja', 'cuenta', 'aportante']);
        $aportante = $comprobante->aportante ?? $ingreso->aportante;

        $pdf = Pdf::loadView('comprobantes.pdf', [
            'comprobante' => $comprobante,
            'ingreso' => $ingreso,
            'aportante' => $aportante,
        ]);

        $contenido = $pdf->output();
        Storage::disk('local')->put($comprobante->ruta_pdf, $contenido);

        return $contenido;
    }

    /**
     * Descarga el PDF del comprobante asegurando su existencia física y estado actualizado.
     */
    public function descargar(ComprobanteDonacion $comprobante): StreamedResponse
    {
        if (! Storage::disk('local')->exists($comprobante->ruta_pdf) || $comprobante->ingreso?->trashed()) {
            $this->regenerarPdf($comprobante);
        }

        return Storage::disk('local')->download(
            $comprobante->ruta_pdf,
            "{$comprobante->numero}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Envía el comprobante en PDF por correo electrónico al aportante.
     *
     * @throws DomainException
     */
    public function enviarPorCorreo(ComprobanteDonacion $comprobante, ?string $correoDestino = null, ?User $operador = null): void
    {
        $correo = trim($correoDestino ?: ($comprobante->aportante?->correo ?? ''));

        if (empty($correo) || ! filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('El aportante no posee una dirección de correo electrónico válida para el envío.');
        }

        // Asegurar que el archivo físico esté generado
        if (! Storage::disk('local')->exists($comprobante->ruta_pdf) || $comprobante->ingreso?->trashed()) {
            $this->regenerarPdf($comprobante);
        }

        // Despachar notificación
        Notification::route('mail', $correo)->notify(new ComprobanteDonacionNotification($comprobante));

        // Registrar timestamp de envío
        $comprobante->enviado_en = now();
        $comprobante->save();

        $this->bitacoraService->registrar(
            'enviar_comprobante',
            $comprobante,
            "Envío de comprobante {$comprobante->numero} al correo {$correo}",
            null,
            ['enviado_en' => $comprobante->enviado_en],
            $operador?->id
        );
    }
}
