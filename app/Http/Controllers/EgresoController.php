<?php

namespace App\Http\Controllers;

use App\Exceptions\MovimientoInvalidoException;
use App\Exceptions\PeriodoBloqueadoException;
use App\Http\Requests\AnularMovimientoRequest;
use App\Http\Requests\StoreEgresoRequest;
use App\Http\Requests\UpdateEgresoRequest;
use App\Models\Caja;
use App\Models\CatalogoEgreso;
use App\Models\Egreso;
use App\Services\MovimientoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * EgresoController
 *
 * Administración y captura rápida de egresos y gastos contables (RN-01..RN-07, RN-10, RN-15).
 */
class EgresoController extends Controller
{
    /**
     * Listado con filtros, paginación 20 y cálculo de total SQL.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Egreso::class);

        $user = $request->user();
        $query = Egreso::query()
            ->with(['caja', 'cuenta', 'usuario'])
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
            $query->where('cuenta_egreso_id', $request->input('cuenta_id'));
        }

        if ($request->filled('buscar')) {
            $buscar = trim($request->input('buscar'));
            $query->where(function ($q) use ($buscar) {
                $q->where('descripcion', 'like', "%{$buscar}%")
                    ->orWhere('referencia', 'like', "%{$buscar}%");
            });
        }

        // Fila de TOTAL calculada directamente en base de datos con SUM (RN-01)
        $totalFiltrado = (clone $query)->sum('monto');

        $egresos = $query->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $cajas = $user->can('cajas.gestionar')
            ? Caja::where('activa', true)->orderBy('nombre')->get()
            : $user->cajas()->where('activa', true)->orderBy('nombre')->get();

        $cuentas = CatalogoEgreso::where('activo', true)
            ->where('es_transferencia', false)
            ->orderBy('codigo')
            ->get();

        return view('egresos.index', compact('egresos', 'totalFiltrado', 'cajas', 'cuentas'));
    }

    /**
     * Formulario para nuevo egreso con preselección de caja activa y foco.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Egreso::class);

        $user = $request->user();
        $cajas = $user->can('cajas.gestionar')
            ? Caja::where('activa', true)->orderBy('nombre')->get()
            : $user->cajas()->where('activa', true)->orderBy('nombre')->get();

        $cuentas = CatalogoEgreso::where('activo', true)
            ->where('es_transferencia', false)
            ->orderBy('codigo')
            ->get();

        $cajaPreseleccionadaId = $request->input('caja_id', session('caja_activa_id'));
        $fechaPreseleccionada = $request->input('fecha', date('Y-m-d'));

        return view('egresos.create', compact('cajas', 'cuentas', 'cajaPreseleccionadaId', 'fechaPreseleccionada'));
    }

    /**
     * Guarda el egreso contable y soporta alerta de saldo negativo (RN-10).
     */
    public function store(StoreEgresoRequest $request, MovimientoService $service): RedirectResponse
    {
        Gate::authorize('create', Egreso::class);

        try {
            $resultado = $service->crearEgreso($request->user(), $request->validated());

            $mensajeExito = 'Egreso registrado exitosamente.';
            if ($resultado['saldo_negativo']) {
                session()->flash('warning', '¡Atención! Tras registrar este egreso, el saldo de la caja quedó en negativo: '.formato_moneda($resultado['saldo_actual']));
            }

            if ($request->has('guardar_y_nuevo')) {
                return redirect()
                    ->route('egresos.create', [
                        'caja_id' => $request->input('caja_id'),
                        'fecha' => $request->input('fecha'),
                    ])
                    ->with('success', $mensajeExito.' Listo para registrar el siguiente.')
                    ->with('foco_cuenta', true);
            }

            return redirect()
                ->route('egresos.index')
                ->with('success', $mensajeExito);
        } catch (PeriodoBloqueadoException|MovimientoInvalidoException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Formulario para editar un egreso existente.
     */
    public function edit(Egreso $egreso): View
    {
        Gate::authorize('update', $egreso);

        $user = auth()->user();
        $cajas = $user->can('cajas.gestionar')
            ? Caja::where('activa', true)->orWhere('id', $egreso->caja_id)->orderBy('nombre')->get()
            : $user->cajas()->where('activa', true)->orWhere('cajas.id', $egreso->caja_id)->orderBy('nombre')->get();

        $cuentas = CatalogoEgreso::where('activo', true)
            ->orWhere('id', $egreso->cuenta_egreso_id)
            ->orderBy('codigo')
            ->get();

        return view('egresos.edit', compact('egreso', 'cajas', 'cuentas'));
    }

    /**
     * Actualiza el egreso contable.
     */
    public function update(UpdateEgresoRequest $request, Egreso $egreso, MovimientoService $service): RedirectResponse
    {
        Gate::authorize('update', $egreso);

        try {
            $resultado = $service->actualizar($request->user(), $egreso, $request->validated());

            if ($resultado['saldo_negativo']) {
                session()->flash('warning', '¡Atención! El saldo actual de la caja se encuentra en negativo: '.formato_moneda($resultado['saldo_actual']));
            }

            return redirect()
                ->route('egresos.index')
                ->with('success', 'Egreso actualizado exitosamente.');
        } catch (PeriodoBloqueadoException|MovimientoInvalidoException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Anula lógicamente un egreso registrando el motivo obligatorio (RN-07).
     */
    public function anular(AnularMovimientoRequest $request, Egreso $egreso, MovimientoService $service): RedirectResponse
    {
        Gate::authorize('anular', $egreso);

        try {
            $service->anular($request->user(), $egreso, $request->input('motivo'));

            return back()->with('success', 'El egreso ha sido anulado correctamente.');
        } catch (PeriodoBloqueadoException|MovimientoInvalidoException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
