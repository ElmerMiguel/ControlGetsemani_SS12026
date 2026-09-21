<?php

namespace App\Http\Controllers;

use App\Enums\TipoDepartamento;
use App\Http\Requests\StoreDepartamentoRequest;
use App\Http\Requests\UpdateDepartamentoRequest;
use App\Models\Departamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * DepartamentoController
 *
 * Módulo de administración de la estructura organizativa (RN-13).
 * Protegido por el permiso 'departamentos.gestionar'.
 */
class DepartamentoController extends Controller
{
    /**
     * Listado con búsqueda y filtros por tipo y estado.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Departamento::class);

        $query = Departamento::withCount('cajas')->orderBy('nombre');

        if ($request->filled('buscar')) {
            $buscar = trim($request->input('buscar'));
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }

        if ($request->filled('estado')) {
            $query->where('activo', $request->input('estado') === 'activo');
        }

        $departamentos = $query->paginate(15)->withQueryString();
        $tipos = TipoDepartamento::cases();

        return view('departamentos.index', compact('departamentos', 'tipos'));
    }

    /**
     * Formulario para crear un nuevo departamento.
     */
    public function create(): View
    {
        Gate::authorize('create', Departamento::class);

        $tipos = TipoDepartamento::cases();

        return view('departamentos.create', compact('tipos'));
    }

    /**
     * Registra un nuevo departamento en el sistema.
     */
    public function store(StoreDepartamentoRequest $request): RedirectResponse
    {
        Gate::authorize('create', Departamento::class);

        Departamento::create(array_merge($request->validated(), [
            'activo' => true,
        ]));

        return redirect()
            ->route('departamentos.index')
            ->with('success', 'Departamento creado exitosamente.');
    }

    /**
     * Formulario de edición de departamento.
     */
    public function edit(Departamento $departamento): View
    {
        Gate::authorize('update', $departamento);

        $tipos = TipoDepartamento::cases();

        return view('departamentos.edit', compact('departamento', 'tipos'));
    }

    /**
     * Actualiza la información de un departamento existente.
     */
    public function update(UpdateDepartamentoRequest $request, Departamento $departamento): RedirectResponse
    {
        Gate::authorize('update', $departamento);

        $departamento->update($request->validated());

        return redirect()
            ->route('departamentos.index')
            ->with('success', 'Departamento actualizado exitosamente.');
    }

    /**
     * Alterna el estado activo/inactivo (RN-13).
     * Un departamento con cajas activas no puede desactivarse.
     */
    public function cambiarEstado(Departamento $departamento): RedirectResponse
    {
        Gate::authorize('toggleEstado', $departamento);

        $nuevoEstado = ! $departamento->activo;
        $departamento->update(['activo' => $nuevoEstado]);

        $mensaje = $nuevoEstado
            ? 'Departamento activado exitosamente.'
            : 'Departamento desactivado exitosamente.';

        return back()->with('success', $mensaje);
    }
}
