<?php

namespace App\Http\Controllers;

use App\Enums\MedioCaja;
use App\Http\Requests\StoreCajaRequest;
use App\Http\Requests\UpdateCajaRequest;
use App\Models\Caja;
use App\Models\Departamento;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * CajaController
 *
 * Administración y visualización de cajas contables de la congregación (RN-13, RN-15).
 */
class CajaController extends Controller
{
    /**
     * Listado de cajas accesibles para el usuario autenticado con filtros.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Caja::class);

        $user = $request->user();

        if ($user->can('cajas.gestionar')) {
            $query = Caja::query();
        } else {
            $query = $user->cajas();
        }

        $query->with(['departamento', 'tesoreros'])->orderBy('nombre');

        if ($request->filled('buscar')) {
            $buscar = trim($request->input('buscar'));
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('codigo', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('departamento_id')) {
            $query->where('departamento_id', $request->input('departamento_id'));
        }

        if ($request->filled('medio')) {
            $query->where('medio', $request->input('medio'));
        }

        if ($request->filled('estado')) {
            $query->where('activa', $request->input('estado') === 'activa');
        }

        $cajas = $query->paginate(15)->withQueryString();
        $departamentos = Departamento::orderBy('nombre')->get(['id', 'nombre']);
        $medios = MedioCaja::cases();

        return view('cajas.index', compact('cajas', 'departamentos', 'medios'));
    }

    /**
     * Formulario para aperturar una nueva caja contable.
     */
    public function create(): View
    {
        Gate::authorize('create', Caja::class);

        $departamentos = Departamento::where('activo', true)->orderBy('nombre')->get();
        $medios = MedioCaja::cases();
        $tesoreros = User::role('tesorero')->where('activo', true)->orderBy('name')->get();

        return view('cajas.create', compact('departamentos', 'medios', 'tesoreros'));
    }

    /**
     * Registra una nueva caja y asigna los tesoreros iniciales.
     */
    public function store(StoreCajaRequest $request): RedirectResponse
    {
        Gate::authorize('create', Caja::class);

        $datos = $request->validated();
        $tesoreros = $datos['tesoreros'] ?? [];
        unset($datos['tesoreros']);

        $datos['activa'] = $request->boolean('activa', true);

        $caja = Caja::create($datos);

        if (! empty($tesoreros)) {
            $caja->tesoreros()->sync($tesoreros);
        }

        return redirect()
            ->route('cajas.index')
            ->with('success', 'Caja registrada y aperturada exitosamente.');
    }

    /**
     * Vista de detalle de caja (esqueleto base para Fase 2 P8).
     */
    public function show(Caja $caja): View
    {
        Gate::authorize('view', $caja);

        $caja->load(['departamento', 'tesoreros']);

        return view('cajas.show', compact('caja'));
    }

    /**
     * Formulario para editar datos y asignaciones de la caja.
     */
    public function edit(Caja $caja): View
    {
        Gate::authorize('update', $caja);

        $caja->load(['departamento', 'tesoreros']);
        $departamentos = Departamento::orderBy('nombre')->get();
        $medios = MedioCaja::cases();
        $tesoreros = User::role('tesorero')->where('activo', true)->orderBy('name')->get();

        return view('cajas.edit', compact('caja', 'departamentos', 'medios', 'tesoreros'));
    }

    /**
     * Actualiza la información de la caja y sus tesoreros asignados.
     */
    public function update(UpdateCajaRequest $request, Caja $caja): RedirectResponse
    {
        Gate::authorize('update', $caja);

        $datos = $request->validated();
        $tesoreros = $datos['tesoreros'] ?? null;
        unset($datos['tesoreros']);

        // Si la caja ya registra movimientos, apertura no se modifica
        if ($caja->tieneMovimientos()) {
            unset($datos['saldo_apertura'], $datos['fecha_apertura']);
        }

        if ($request->has('activa')) {
            $datos['activa'] = $request->boolean('activa');
        }

        $caja->update($datos);

        if ($tesoreros !== null) {
            $caja->tesoreros()->sync($tesoreros);
        }

        return redirect()
            ->route('cajas.index')
            ->with('success', 'Caja actualizada exitosamente.');
    }

    /**
     * Alterna el estado activa/inactiva de la caja (RN-13).
     */
    public function cambiarEstado(Caja $caja): RedirectResponse
    {
        Gate::authorize('toggleEstado', $caja);

        $nuevoEstado = ! $caja->activa;
        $caja->update(['activa' => $nuevoEstado]);

        $mensaje = $nuevoEstado
            ? 'Caja activada exitosamente.'
            : 'Caja desactivada exitosamente.';

        return back()->with('success', $mensaje);
    }
}
