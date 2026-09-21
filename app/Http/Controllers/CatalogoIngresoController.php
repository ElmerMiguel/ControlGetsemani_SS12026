<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCatalogoIngresoRequest;
use App\Http\Requests\UpdateCatalogoIngresoRequest;
use App\Models\CatalogoIngreso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * CatalogoIngresoController
 *
 * Módulo de cuentas de ingresos (RN-12).
 * Lectura disponible para usuarios con 'catalogos.ver'.
 * Escritura restringida a administradores con 'catalogos.gestionar'.
 */
class CatalogoIngresoController extends Controller
{
    /**
     * Listado con búsqueda por código o nombre y filtro de estado.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CatalogoIngreso::class);

        $query = CatalogoIngreso::orderBy('codigo');

        if ($request->filled('buscar')) {
            $buscar = trim($request->input('buscar'));
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('activo', $request->input('estado') === 'activo');
        }

        $cuentas = $query->paginate(15)->withQueryString();

        return view('catalogos.ingresos.index', compact('cuentas'));
    }

    /**
     * Formulario para crear una nueva cuenta de ingreso.
     */
    public function create(): View
    {
        Gate::authorize('create', CatalogoIngreso::class);

        return view('catalogos.ingresos.create');
    }

    /**
     * Registra una nueva cuenta de ingreso en el catálogo.
     */
    public function store(StoreCatalogoIngresoRequest $request): RedirectResponse
    {
        Gate::authorize('create', CatalogoIngreso::class);

        CatalogoIngreso::create(array_merge($request->validated(), [
            'es_transferencia' => false,
            'activo' => true,
        ]));

        return redirect()
            ->route('catalogos.ingresos.index')
            ->with('success', 'Cuenta de ingreso creada exitosamente.');
    }

    /**
     * Formulario para editar una cuenta existente.
     */
    public function edit(CatalogoIngreso $catalogoIngreso): View
    {
        Gate::authorize('update', $catalogoIngreso);

        return view('catalogos.ingresos.edit', compact('catalogoIngreso'));
    }

    /**
     * Actualiza la información de la cuenta de ingreso.
     */
    public function update(UpdateCatalogoIngresoRequest $request, CatalogoIngreso $catalogoIngreso): RedirectResponse
    {
        Gate::authorize('update', $catalogoIngreso);

        $catalogoIngreso->update($request->validated());

        return redirect()
            ->route('catalogos.ingresos.index')
            ->with('success', 'Cuenta de ingreso actualizada exitosamente.');
    }

    /**
     * Alterna el estado activo/inactivo (RN-12).
     */
    public function cambiarEstado(CatalogoIngreso $catalogoIngreso): RedirectResponse
    {
        Gate::authorize('toggleEstado', $catalogoIngreso);

        $nuevoEstado = ! $catalogoIngreso->activo;
        $catalogoIngreso->update(['activo' => $nuevoEstado]);

        $mensaje = $nuevoEstado
            ? 'Cuenta de ingreso activada exitosamente.'
            : 'Cuenta de ingreso desactivada exitosamente.';

        return back()->with('success', $mensaje);
    }
}
