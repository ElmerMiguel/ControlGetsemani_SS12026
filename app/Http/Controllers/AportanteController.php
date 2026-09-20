<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAportanteRequest;
use App\Http\Requests\UpdateAportanteRequest;
use App\Models\Aportante;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * AportanteController
 *
 * Administración del padrón de aportantes y donantes (RN-12).
 * Protegido por el permiso 'aportantes.gestionar'.
 */
class AportanteController extends Controller
{
    /**
     * Listado con búsqueda por nombre, CUI o teléfono, y filtro de estado.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Aportante::class);

        $query = Aportante::orderBy('nombre_completo');

        if ($request->filled('buscar')) {
            $buscar = trim($request->input('buscar'));
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre_completo', 'like', "%{$buscar}%")
                    ->orWhere('cui_dpi', 'like', "%{$buscar}%")
                    ->orWhere('telefono', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('activo', $request->input('estado') === 'activo');
        }

        $aportantes = $query->paginate(15)->withQueryString();

        return view('aportantes.index', compact('aportantes'));
    }

    /**
     * Formulario para registrar un nuevo aportante.
     */
    public function create(): View
    {
        Gate::authorize('create', Aportante::class);

        return view('aportantes.create');
    }

    /**
     * Registra un nuevo aportante.
     */
    public function store(StoreAportanteRequest $request): RedirectResponse
    {
        Gate::authorize('create', Aportante::class);

        Aportante::create(array_merge($request->validated(), [
            'activo' => true,
        ]));

        return redirect()
            ->route('aportantes.index')
            ->with('success', 'Aportante registrado exitosamente.');
    }

    /**
     * Formulario para editar un aportante existente.
     */
    public function edit(Aportante $aportante): View
    {
        Gate::authorize('update', $aportante);

        return view('aportantes.edit', compact('aportante'));
    }

    /**
     * Actualiza la información del aportante.
     */
    public function update(UpdateAportanteRequest $request, Aportante $aportante): RedirectResponse
    {
        Gate::authorize('update', $aportante);

        $aportante->update($request->validated());

        return redirect()
            ->route('aportantes.index')
            ->with('success', 'Aportante actualizado exitosamente.');
    }

    /**
     * Alterna el estado activo/inactivo del aportante.
     */
    public function cambiarEstado(Aportante $aportante): RedirectResponse
    {
        Gate::authorize('toggleEstado', $aportante);

        $nuevoEstado = ! $aportante->activo;
        $aportante->update(['activo' => $nuevoEstado]);

        $mensaje = $nuevoEstado
            ? 'Aportante activado exitosamente.'
            : 'Aportante desactivado exitosamente.';

        return back()->with('success', $mensaje);
    }
}
