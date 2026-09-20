<?php

namespace App\Http\Controllers;

use App\Exceptions\MovimientoInvalidoException;
use App\Exceptions\PeriodoBloqueadoException;
use App\Http\Requests\AnularMovimientoRequest;
use App\Http\Requests\StoreIngresoRequest;
use App\Http\Requests\UpdateIngresoRequest;
use App\Models\Aportante;
use App\Models\Caja;
use App\Models\CatalogoIngreso;
use App\Models\Ingreso;
use App\Services\MovimientoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * IngresoController
 *
 * Administración y captura rápida de ingresos contables (RN-01..RN-07, RN-15).
 */
class IngresoController extends Controller
{
    /**
     * Listado con filtros, paginación 20 y cálculo de total SQL.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Ingreso::class);

        $user = $request->user();
        $query = Ingreso::query()
            ->with(['caja', 'cuenta', 'aportante', 'usuario'])
            ->accesiblesPara($user);

        // Filtro de anulados exclusivo para administrador
        if ($user->can('cajas.gestionar') && $request->boolean('anulados')) {
            $query->withTrashed();
        }

        // Filtro por caja (específica o predeterminada por sesión)
        if ($request->filled('caja_id')) {
            $query->where('caja_id', $request->input('caja_id'));
        } elseif ($request->session()->has('caja_activa_id')) {
            $cajaActivaId = $request->session()->get('caja_activa_id');
            if ($cajaActivaId !== 'todas') {
                $query->where('caja_id', $cajaActivaId);
            }
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->input('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->input('fecha_hasta'));
        }

        if ($request->filled('cuenta_id')) {
            $query->where('cuenta_ingreso_id', $request->input('cuenta_id'));
        }

        if ($request->filled('buscar')) {
            $buscar = trim($request->input('buscar'));
            $query->where(function ($q) use ($buscar) {
                $q->where('recibo', 'like', "%{$buscar}%")
                    ->orWhere('observaciones', 'like', "%{$buscar}%")
                    ->orWhereHas('aportante', fn ($sq) => $sq->where('nombre_completo', 'like', "%{$buscar}%"));
            });
        }

        // Fila de TOTAL calculada directamente en base de datos con SUM (RN-01)
        $totalFiltrado = (clone $query)->sum('monto');

        $ingresos = $query->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $cajas = $user->can('cajas.gestionar')
            ? Caja::where('activa', true)->orderBy('nombre')->get()
            : $user->cajas()->where('activa', true)->orderBy('nombre')->get();

        $cuentas = CatalogoIngreso::where('activo', true)
            ->where('es_transferencia', false)
            ->orderBy('codigo')
            ->get();

        return view('ingresos.index', compact('ingresos', 'totalFiltrado', 'cajas', 'cuentas'));
    }

    /**
     * Formulario para nuevo ingreso con preselección de caja activa y foco.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Ingreso::class);

        $user = $request->user();
        $cajas = $user->can('cajas.gestionar')
            ? Caja::where('activa', true)->orderBy('nombre')->get()
            : $user->cajas()->where('activa', true)->orderBy('nombre')->get();

        $cuentas = CatalogoIngreso::where('activo', true)
            ->where('es_transferencia', false)
            ->orderBy('codigo')
            ->get();

        $aportantes = Aportante::where('activo', true)->orderBy('nombre_completo')->get();

        $cajaPreseleccionadaId = $request->input('caja_id', session('caja_activa_id'));
        $fechaPreseleccionada = $request->input('fecha', date('Y-m-d'));

        return view('ingresos.create', compact('cajas', 'cuentas', 'aportantes', 'cajaPreseleccionadaId', 'fechaPreseleccionada'));
    }

    /**
     * Guarda el ingreso contable y soporta opción "Guardar y nuevo".
     */
    public function store(StoreIngresoRequest $request, MovimientoService $service): RedirectResponse
    {
        Gate::authorize('create', Ingreso::class);

        try {
            $service->crearIngreso($request->user(), $request->validated());

            if ($request->has('guardar_y_nuevo')) {
                return redirect()
                    ->route('ingresos.create', [
                        'caja_id' => $request->input('caja_id'),
                        'fecha' => $request->input('fecha'),
                    ])
                    ->with('success', 'Ingreso registrado correctamente. Listo para el siguiente.')
                    ->with('foco_cuenta', true);
            }

            return redirect()
                ->route('ingresos.index')
                ->with('success', 'Ingreso registrado exitosamente.');
        } catch (PeriodoBloqueadoException|MovimientoInvalidoException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Formulario para editar un ingreso existente.
     */
    public function edit(Ingreso $ingreso): View
    {
        Gate::authorize('update', $ingreso);

        $user = auth()->user();
        $cajas = $user->can('cajas.gestionar')
            ? Caja::where('activa', true)->orWhere('id', $ingreso->caja_id)->orderBy('nombre')->get()
            : $user->cajas()->where('activa', true)->orWhere('cajas.id', $ingreso->caja_id)->orderBy('nombre')->get();

        $cuentas = CatalogoIngreso::where('activo', true)
            ->orWhere('id', $ingreso->cuenta_ingreso_id)
            ->orderBy('codigo')
            ->get();

        $aportantes = Aportante::where('activo', true)
            ->orWhere('id', $ingreso->aportante_id)
            ->orderBy('nombre_completo')
            ->get();

        return view('ingresos.edit', compact('ingreso', 'cajas', 'cuentas', 'aportantes'));
    }

    /**
     * Actualiza el ingreso contable.
     */
    public function update(UpdateIngresoRequest $request, Ingreso $ingreso, MovimientoService $service): RedirectResponse
    {
        Gate::authorize('update', $ingreso);

        try {
            $service->actualizar($request->user(), $ingreso, $request->validated());

            return redirect()
                ->route('ingresos.index')
                ->with('success', 'Ingreso actualizado exitosamente.');
        } catch (PeriodoBloqueadoException|MovimientoInvalidoException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Anula lógicamente un ingreso registrando el motivo obligatorio (RN-07).
     */
    public function anular(AnularMovimientoRequest $request, Ingreso $ingreso, MovimientoService $service): RedirectResponse
    {
        Gate::authorize('anular', $ingreso);

        try {
            $service->anular($request->user(), $ingreso, $request->input('motivo'));

            return back()->with('success', 'El ingreso ha sido anulado correctamente.');
        } catch (PeriodoBloqueadoException|MovimientoInvalidoException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
