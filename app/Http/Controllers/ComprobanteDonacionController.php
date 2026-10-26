<?php

namespace App\Http\Controllers;

use App\Models\ComprobanteDonacion;
use App\Models\Ingreso;
use App\Services\ComprobanteService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controlador de Comprobantes de Donación en PDF (RF-07 / RF-14 / RN3-02)
 */
class ComprobanteDonacionController extends Controller
{
    public function __construct(
        protected ComprobanteService $comprobanteService
    ) {}

    /**
     * Genera un comprobante formal de donación para el ingreso especificado.
     */
    public function generar(Request $request, Ingreso $ingreso): RedirectResponse|StreamedResponse
    {
        Gate::authorize('generar', [ComprobanteDonacion::class, $ingreso]);

        try {
            $comprobante = $this->comprobanteService->generar($ingreso, $request->user());

            if ($request->boolean('descargar_directo')) {
                return $this->comprobanteService->descargar($comprobante);
            }

            return back()->with('status', "Comprobante {$comprobante->numero} generado exitosamente.");
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Descarga el archivo PDF del comprobante formal.
     */
    public function descargar(Request $request, ComprobanteDonacion $comprobante): StreamedResponse
    {
        Gate::authorize('descargar', $comprobante);

        return $this->comprobanteService->descargar($comprobante);
    }

    /**
     * Envía por correo electrónico el comprobante en PDF al aportante.
     */
    public function enviar(Request $request, ComprobanteDonacion $comprobante): RedirectResponse
    {
        Gate::authorize('enviar', $comprobante);

        $request->validate([
            'correo' => ['nullable', 'email', 'max:255'],
        ]);

        try {
            $this->comprobanteService->enviarPorCorreo(
                $comprobante,
                $request->input('correo'),
                $request->user()
            );

            return back()->with('status', "Comprobante {$comprobante->numero} enviado exitosamente por correo electrónico.");
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
