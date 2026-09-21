<?php

namespace App\Services;

namespace App\Http\Controllers;

use App\Exports\ReporteCajaExport;
use App\Models\Caja;
use App\Services\BitacoraService;
use App\Services\ReporteCajaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ReporteController extends Controller
{
    public function __construct(
        protected ReporteCajaService $reporteService,
        protected BitacoraService $bitacoraService
    ) {}

    /**
     * Muestra la vista HTML del reporte financiero de caja con filtros y desglose.
     */
    public function caja(Request $request): Response
    {
        Gate::authorize('reportes.ver');

        $user = $request->user();
        $cajasAccesibles = Caja::accesiblesPara($user)->with('departamento')->orderBy('nombre')->get();

        if ($cajasAccesibles->isEmpty()) {
            abort(403, 'No tiene cajas asignadas para consultar reportes.');
        }

        // Selección de caja
        $cajaId = $request->input('caja_id');
        if (! $cajaId) {
            $cajaActivaId = $request->session()->get('caja_activa_id');
            $caja = ($cajaActivaId && $cajaActivaId !== 'todas')
                ? ($cajasAccesibles->firstWhere('id', $cajaActivaId) ?? $cajasAccesibles->first())
                : $cajasAccesibles->first();
        } else {
            $caja = Caja::with('departamento')->where('id', $cajaId)->first();
            if (! $caja) {
                abort(404, 'Caja no encontrada.');
            }
            if (! $user->tieneCaja($caja)) {
                abort(403, 'No tiene autorización para consultar reportes de esta caja.');
            }
        }

        // Rango de fechas (por defecto: mes en curso)
        $desdeInput = $request->input('desde', now()->startOfMonth()->format('Y-m-d'));
        $hastaInput = $request->input('hasta', now()->endOfMonth()->format('Y-m-d'));

        $this->validarRangoFechas($desdeInput, $hastaInput);

        $datos = $this->reporteService->generar($caja, $desdeInput, $hastaInput);

        return response()->view('reportes.caja', [
            'datos' => $datos,
            'cajaSeleccionada' => $caja,
            'cajas' => $cajasAccesibles,
            'desde' => $desdeInput,
            'hasta' => $hastaInput,
        ]);
    }

    /**
     * Exporta el reporte de caja a formato PDF (A4 horizontal con DejaVu Sans).
     */
    public function pdf(Request $request): Response
    {
        Gate::authorize('reportes.exportar');

        $user = $request->user();
        $caja = $this->obtenerCajaValidada($request, $user);

        $desdeInput = $request->input('desde', now()->startOfMonth()->format('Y-m-d'));
        $hastaInput = $request->input('hasta', now()->endOfMonth()->format('Y-m-d'));
        $this->validarRangoFechas($desdeInput, $hastaInput);

        $datos = $this->reporteService->generar($caja, $desdeInput, $hastaInput);

        // Registrar auditoría (RN-16)
        $this->bitacoraService->registrar(
            'reporte.exportado',
            $caja,
            "Exportó reporte de caja en formato PDF para el rango {$desdeInput} a {$hastaInput}",
            null,
            ['formato' => 'pdf', 'desde' => $desdeInput, 'hasta' => $hastaInput, 'caja_id' => $caja->id]
        );

        $pdf = Pdf::loadView('reportes.pdf.caja', $datos)
            ->setPaper('a4', 'landscape');

        $nombreArchivo = 'reporte_caja_'.$caja->codigo.'_'.$desdeInput.'_'.$hastaInput.'.pdf';

        return $pdf->download($nombreArchivo);
    }

    /**
     * Exporta el reporte de caja a formato Excel (.xlsx) con múltiples hojas.
     */
    public function xlsx(Request $request): BinaryFileResponse
    {
        Gate::authorize('reportes.exportar');

        $user = $request->user();
        $caja = $this->obtenerCajaValidada($request, $user);

        $desdeInput = $request->input('desde', now()->startOfMonth()->format('Y-m-d'));
        $hastaInput = $request->input('hasta', now()->endOfMonth()->format('Y-m-d'));
        $this->validarRangoFechas($desdeInput, $hastaInput);

        $datos = $this->reporteService->generar($caja, $desdeInput, $hastaInput);

        // Registrar auditoría (RN-16)
        $this->bitacoraService->registrar(
            'reporte.exportado',
            $caja,
            "Exportó reporte de caja en formato XLSX para el rango {$desdeInput} a {$hastaInput}",
            null,
            ['formato' => 'xlsx', 'desde' => $desdeInput, 'hasta' => $hastaInput, 'caja_id' => $caja->id]
        );

        $nombreArchivo = 'reporte_caja_'.$caja->codigo.'_'.$desdeInput.'_'.$hastaInput.'.xlsx';

        return Excel::download(new ReporteCajaExport($datos), $nombreArchivo);
    }

    /**
     * Valida que la caja exista y sea accesible para el usuario autenticado (IDOR check).
     */
    protected function obtenerCajaValidada(Request $request, $user): Caja
    {
        $cajaId = $request->input('caja_id');
        if (! $cajaId) {
            $cajasAccesibles = Caja::accesiblesPara($user)->with('departamento')->get();
            $cajaActivaId = $request->session()->get('caja_activa_id');
            $caja = ($cajaActivaId && $cajaActivaId !== 'todas')
                ? ($cajasAccesibles->firstWhere('id', $cajaActivaId) ?? $cajasAccesibles->first())
                : $cajasAccesibles->first();

            if (! $caja) {
                abort(403, 'No tiene cajas accesibles.');
            }

            return $caja;
        }

        $caja = Caja::with('departamento')->where('id', $cajaId)->first();
        if (! $caja) {
            abort(404, 'Caja no encontrada.');
        }

        if (! $user->tieneCaja($caja)) {
            abort(403, 'No tiene autorización para consultar reportes de esta caja.');
        }

        return $caja;
    }

    /**
     * Valida el rango de fechas (máximo 366 días y desde <= hasta).
     */
    protected function validarRangoFechas(string $desde, string $hasta): void
    {
        try {
            $fechaDesde = Carbon::parse($desde);
            $fechaHasta = Carbon::parse($hasta);
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'desde' => 'Las fechas ingresadas no tienen un formato válido.',
            ]);
        }

        if ($fechaDesde->greaterThan($fechaHasta)) {
            throw ValidationException::withMessages([
                'desde' => 'La fecha inicial no puede ser posterior a la fecha final.',
            ]);
        }

        if ($fechaDesde->diffInDays($fechaHasta) > 366) {
            throw ValidationException::withMessages([
                'hasta' => 'El rango del reporte no puede exceder 366 días (1 año calendario).',
            ]);
        }
    }
}
