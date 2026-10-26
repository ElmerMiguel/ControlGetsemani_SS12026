<?php

namespace App\Http\Controllers;

use App\Exports\InformeGeneralExport;
use App\Services\BitacoraService;
use App\Services\InformeGeneralService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador para el Informe General de Asamblea (Consolidado Institucional)
 *
 * Implementa RF-07, RN3-03, RN3-04 y D3-10.
 */
class InformeGeneralController extends Controller
{
    public function __construct(
        protected InformeGeneralService $informeService,
        protected BitacoraService $bitacoraService
    ) {}

    /**
     * Muestra la pantalla consolidada del Informe General de Asamblea con filtros.
     */
    public function index(Request $request): View
    {
        Gate::authorize('informe_general.ver');

        $desde = $request->input('desde', now()->startOfYear()->format('Y-m-d'));
        $hasta = $request->input('hasta', now()->endOfYear()->format('Y-m-d'));

        $this->validarFechas($desde, $hasta);

        $datos = $this->informeService->generar($desde, $hasta);

        return view('informe_general.index', [
            'datos' => $datos,
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
    }

    /**
     * Exporta el Informe General a formato PDF oficial para la Asamblea.
     */
    public function pdf(Request $request): Response
    {
        Gate::authorize('informe_general.exportar');

        $desde = $request->input('desde', now()->startOfYear()->format('Y-m-d'));
        $hasta = $request->input('hasta', now()->endOfYear()->format('Y-m-d'));

        $this->validarFechas($desde, $hasta);

        $datos = $this->informeService->generar($desde, $hasta);

        $this->bitacoraService->registrar(
            'informe_general.exportar',
            null,
            "Exportó Informe General de Asamblea en formato PDF para el rango {$desde} a {$hasta}",
            null,
            ['formato' => 'pdf', 'desde' => $desde, 'hasta' => $hasta],
            $request->user()->id
        );

        $pdf = Pdf::loadView('informe_general.pdf', $datos)
            ->setPaper('letter', 'landscape');

        $nombreArchivo = "informe_general_asamblea_{$desde}_{$hasta}.pdf";

        return $pdf->download($nombreArchivo);
    }

    /**
     * Exporta el Informe General a Microsoft Excel (.xlsx) con hojas desglosadas.
     */
    public function xlsx(Request $request): BinaryFileResponse
    {
        Gate::authorize('informe_general.exportar');

        $desde = $request->input('desde', now()->startOfYear()->format('Y-m-d'));
        $hasta = $request->input('hasta', now()->endOfYear()->format('Y-m-d'));

        $this->validarFechas($desde, $hasta);

        $datos = $this->informeService->generar($desde, $hasta);

        $this->bitacoraService->registrar(
            'informe_general.exportar',
            null,
            "Exportó Informe General de Asamblea en formato Excel (.xlsx) para el rango {$desde} a {$hasta}",
            null,
            ['formato' => 'xlsx', 'desde' => $desde, 'hasta' => $hasta],
            $request->user()->id
        );

        $nombreArchivo = "informe_general_asamblea_{$desde}_{$hasta}.xlsx";

        return Excel::download(new InformeGeneralExport($datos), $nombreArchivo);
    }

    /**
     * Valida el formato y coherencia temporal del rango de fechas.
     */
    private function validarFechas(string $desde, string $hasta): void
    {
        try {
            $fDesde = Carbon::createFromFormat('Y-m-d', $desde);
            $fHasta = Carbon::createFromFormat('Y-m-d', $hasta);

            if (! $fDesde || ! $fHasta || $fDesde->gt($fHasta)) {
                throw new \InvalidArgumentException;
            }
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'desde' => 'El rango de fechas ingresado es inválido o la fecha de inicio es posterior a la de fin.',
            ]);
        }
    }
}
