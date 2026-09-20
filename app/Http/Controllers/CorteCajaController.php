<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCorte;
use App\Http\Requests\ReabrirCorteRequest;
use App\Http\Requests\RechazarCorteRequest;
use App\Http\Requests\SolicitarCorteRequest;
use App\Models\Caja;
use App\Models\CorteCaja;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Services\CorteCajaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CorteCajaController extends Controller
{
    /**
     * Listado filtrable de cortes de caja accesibles.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CorteCaja::class);

        $user = $request->user();
        $query = CorteCaja::accesiblesPara($user)
            ->with(['caja.departamento', 'solicitante', 'revisor'])
            ->latest('periodo_fin')
            ->latest('id');

        if ($request->filled('caja_id')) {
            $query->where('caja_id', $request->input('caja_id'));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        $cortes = $query->paginate(15)->withQueryString();
        $cajas = Caja::accesiblesPara($user)->orderBy('codigo')->get();
        $estados = EstadoCorte::cases();

        return view('cortes.index', compact('cortes', 'cajas', 'estados'));
    }

    /**
     * Formulario de solicitud de corte de caja con cálculo previo.
     */
    public function create(Request $request, CorteCajaService $corteService): View
    {
        $user = $request->user();
        $cajas = Caja::accesiblesPara($user)->where('activa', true)->orderBy('codigo')->get();

        if ($cajas->isEmpty()) {
            abort(403, 'No tiene cajas asignadas para solicitar cortes.');
        }

        $cajaSeleccionadaId = $request->input('caja_id', session('caja_activa_id', $cajas->first()->id));
        $cajaSeleccionada = $cajas->firstWhere('id', $cajaSeleccionadaId) ?? $cajas->first();

        Gate::authorize('create', [CorteCaja::class, $cajaSeleccionada]);

        $sugerido = $corteService->periodoSugerido($cajaSeleccionada);

        return view('cortes.create', compact('cajas', 'cajaSeleccionada', 'sugerido'));
    }

    /**
     * Vista previa en vivo del snapshot contable vía AJAX.
     */
    public function preview(Request $request, CorteCajaService $corteService): JsonResponse
    {
        $request->validate([
            'caja_id' => ['required', 'exists:cajas,id'],
            'periodo_fin' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $caja = Caja::findOrFail($request->input('caja_id'));
        Gate::authorize('create', [CorteCaja::class, $caja]);

        $preview = $corteService->previewSnapshot($caja, $request->input('periodo_fin'));

        return response()->json([
            'success' => true,
            'data' => $preview,
            'formateado' => [
                'periodo_inicio' => Carbon::parse($preview['periodo_inicio'])->format('d/m/Y'),
                'periodo_fin' => Carbon::parse($preview['periodo_fin'])->format('d/m/Y'),
                'saldo_inicial' => formato_moneda($preview['saldo_inicial']),
                'total_ingresos' => formato_moneda($preview['total_ingresos']),
                'total_egresos' => formato_moneda($preview['total_egresos']),
                'saldo_final' => formato_moneda($preview['saldo_final']),
            ],
        ]);
    }

    /**
     * Persistencia de la solicitud de corte (RN-08).
     */
    public function store(SolicitarCorteRequest $request, CorteCajaService $corteService): RedirectResponse
    {
        $caja = Caja::findOrFail($request->input('caja_id'));
        Gate::authorize('create', [CorteCaja::class, $caja]);

        $corte = $corteService->solicitar(
            $request->user(),
            $caja,
            $request->input('periodo_fin'),
            $request->input('observaciones')
        );

        return redirect()
            ->route('cortes.show', $corte)
            ->with('success', 'Solicitud de corte de caja enviada exitosamente. El período ha quedado bloqueado para revisión.');
    }

    /**
     * Vista detallada del corte, movimientos incluidos y acciones según rol.
     */
    public function show(CorteCaja $corte): View
    {
        Gate::authorize('view', $corte);

        $corte->load(['caja.departamento', 'solicitante', 'revisor']);

        // Movimientos comprendidos en el período del corte
        $ingresos = Ingreso::where('caja_id', $corte->caja_id)
            ->whereDate('fecha', '>=', $corte->periodo_inicio)
            ->whereDate('fecha', '<=', $corte->periodo_fin)
            ->with(['cuenta', 'aportante'])
            ->latest('fecha')
            ->latest('id')
            ->get()
            ->map(fn ($m) => tap($m, fn ($i) => $i->tipo_movimiento = 'ingreso'));

        $egresos = Egreso::where('caja_id', $corte->caja_id)
            ->whereDate('fecha', '>=', $corte->periodo_inicio)
            ->whereDate('fecha', '<=', $corte->periodo_fin)
            ->with(['cuenta'])
            ->latest('fecha')
            ->latest('id')
            ->get()
            ->map(fn ($m) => tap($m, fn ($e) => $e->tipo_movimiento = 'egreso'));

        $movimientos = $ingresos->concat($egresos)
            ->sort(function ($a, $b) {
                if ($a->fecha->equalTo($b->fecha)) {
                    return $b->id <=> $a->id;
                }

                return $b->fecha <=> $a->fecha;
            })
            ->values();

        // Determinar si es el último corte aprobado de la caja
        $ultimoAprobadoId = CorteCaja::where('caja_id', $corte->caja_id)
            ->where('estado', EstadoCorte::Aprobado)
            ->latest('periodo_fin')
            ->latest('id')
            ->value('id');

        $esUltimoAprobado = ($corte->estado === EstadoCorte::Aprobado && $corte->id === $ultimoAprobadoId);

        return view('cortes.show', compact('corte', 'movimientos', 'esUltimoAprobado'));
    }

    /**
     * RN-09: Aprobación de corte de caja por el Administrador General.
     */
    public function aprobar(Request $request, CorteCaja $corte, CorteCajaService $corteService): RedirectResponse
    {
        Gate::authorize('aprobar', $corte);

        $corteService->aprobar($request->user(), $corte);

        return redirect()
            ->route('cortes.show', $corte)
            ->with('success', 'El corte de caja ha sido aprobado y verificado exitosamente.');
    }

    /**
     * RN-09: Rechazo de corte de caja con observación explicativa obligatoria.
     */
    public function rechazar(RechazarCorteRequest $request, CorteCaja $corte, CorteCajaService $corteService): RedirectResponse
    {
        Gate::authorize('rechazar', $corte);

        $corteService->rechazar($request->user(), $corte, $request->input('observaciones'));

        return redirect()
            ->route('cortes.show', $corte)
            ->with('success', 'El corte de caja ha sido rechazado. El período ha sido desbloqueado para correcciones.');
    }

    /**
     * RN-09: Reapertura del último corte aprobado para ajustes contables.
     */
    public function reabrir(ReabrirCorteRequest $request, CorteCaja $corte, CorteCajaService $corteService): RedirectResponse
    {
        Gate::authorize('reabrir', $corte);

        $corteService->reabrir($request->user(), $corte, $request->input('observaciones'));

        return redirect()
            ->route('cortes.show', $corte)
            ->with('success', 'El corte de caja ha sido reabierto exitosamente. El período contable vuelve a estar editable.');
    }
}
