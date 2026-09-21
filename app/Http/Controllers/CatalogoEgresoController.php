<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCatalogoEgresoRequest;
use App\Http\Requests\UpdateCatalogoEgresoRequest;
use App\Models\CatalogoEgreso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * CatalogoEgresoController
 *
 * Módulo de cuentas de egresos (RN-12).
 * Lectura disponible para usuarios con 'catalogos.ver'.
 * Escritura restringida a administradores con 'catalogos.gestionar'.
 */
class CatalogoEgresoController extends Controller
{
    /**
     * Listado con búsqueda por código o nombre y filtro de estado.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CatalogoEgreso::class);

        $query = CatalogoEgreso::orderBy('codigo');

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

        return view('catalogos.egresos.index', compact('cuentas'));
    }

    /**
     * Formulario para crear una nueva cuenta de egreso.
     */
    public function create(): View
    {
        Gate::authorize('create', CatalogoEgreso::class);

        return view('catalogos.egresos.create');
    }

    /**
     * Registra una nueva cuenta de egreso en el catálogo.
     */
    public function store(StoreCatalogoEgresoRequest $request): RedirectResponse
    {
        Gate::authorize('create', CatalogoEgreso::class);

        CatalogoEgreso::create(array_merge($request->validated(), [
            'es_transferencia' => false,
            'activo' => true,
        ]));

        return redirect()
            ->route('catalogos.egresos.index')
            ->with('success', 'Cuenta de egreso creada exitosamente.');
    }

    /**
     * Formulario para editar una cuenta existente.
     */
    public function edit(CatalogoEgreso $catalogoEgreso): View
    {
        Gate::authorize('update', $catalogoEgreso);

        return view('catalogos.egresos.edit', compact('catalogoEgreso'));
    }

    /**
     * Actualiza la información de la cuenta de egreso.
     */
    public function update(UpdateCatalogoEgresoRequest $request, CatalogoEgreso $catalogoEgreso): RedirectResponse
    {
        Gate::authorize('update', $catalogoEgreso);

        $catalogoEgreso->update($request->validated());

        return redirect()
            ->route('catalogos.egresos.index')
            ->with('success', 'Cuenta de egreso actualizada exitosamente.');
    }

    /**
     * Alterna el estado activo/inactivo (RN-12).
     */
    public function cambiarEstado(CatalogoEgreso $catalogoEgreso): RedirectResponse
    {
        Gate::authorize('toggleEstado', $catalogoEgreso);

        $nuevoEstado = ! $catalogoEgreso->activo;
        $catalogoEgreso->update(['activo' => $nuevoEstado]);

        $mensaje = $nuevoEstado
            ? 'Cuenta de egreso activada exitosamente.'
            : 'Cuenta de egreso desactivada exitosamente.';

        return back()->with('success', $mensaje);
    }
}
