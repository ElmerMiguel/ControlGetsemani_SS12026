<?php

namespace App\Http\Controllers;

use App\Enums\EstadoBien;
use App\Http\Requests\Inventario\CambiarEstadoInventarioRequest;
use App\Http\Requests\Inventario\StoreInventarioRequest;
use App\Http\Requests\Inventario\UpdateInventarioRequest;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Services\InventarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Controlador de Inventario Institucional (RF-09)
 */
class InventarioController extends Controller
{
    public function __construct(
        protected InventarioService $inventarioService
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Inventario::class);

        $user = $request->user();

        // Departamentos accesibles según rol
        $departamentos = $user->hasRole('admin')
            ? Departamento::where('activo', true)->orderBy('nombre')->get()
            : Departamento::whereHas('cajas', function ($q) use ($user) {
                $q->whereIn('cajas.id', $user->cajas()->pluck('cajas.id'));
            })->where('activo', true)->orderBy('nombre')->get();

        $query = Inventario::with(['departamento', 'usuario'])
            ->accesiblesPara($user)
            ->latest('fecha_registro');

        // Filtros
        if ($request->filled('departamento_id')) {
            $query->where('departamento_id', $request->input('departamento_id'));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        if ($request->filled('buscar')) {
            $termino = trim($request->input('buscar'));
            $query->where(function ($q) use ($termino) {
                $q->where('codigo', 'like', "%{$termino}%")
                    ->orWhere('nombre', 'like', "%{$termino}%")
                    ->orWhere('descripcion', 'like', "%{$termino}%");
            });
        }

        $bienes = $query->paginate(15)->withQueryString();
        $estados = EstadoBien::cases();
        $conteoPorEstado = $this->inventarioService->resumenPorEstado($user);

        return view('inventario.index', compact('bienes', 'departamentos', 'estados', 'conteoPorEstado'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Inventario::class);

        $user = $request->user();
        $departamentos = $user->hasRole('admin')
            ? Departamento::where('activo', true)->orderBy('nombre')->get()
            : Departamento::whereHas('cajas', function ($q) use ($user) {
                $q->whereIn('cajas.id', $user->cajas()->pluck('cajas.id'));
            })->where('activo', true)->orderBy('nombre')->get();

        $estados = EstadoBien::cases();

        return view('inventario.create', compact('departamentos', 'estados'));
    }

    public function store(StoreInventarioRequest $request): RedirectResponse
    {
        $departamento = Departamento::findOrFail($request->input('departamento_id'));
        Gate::authorize('create', [Inventario::class, $departamento]);

        $this->inventarioService->crear($request->user(), $request->validated());

        return redirect()->route('inventario.index')
            ->with('status', 'Bien institucional registrado exitosamente.');
    }

    public function edit(Inventario $inventario): View
    {
        Gate::authorize('update', $inventario);

        $user = request()->user();
        $departamentos = $user->hasRole('admin')
            ? Departamento::where('activo', true)->orderBy('nombre')->get()
            : Departamento::whereHas('cajas', function ($q) use ($user) {
                $q->whereIn('cajas.id', $user->cajas()->pluck('cajas.id'));
            })->where('activo', true)->orderBy('nombre')->get();

        $estados = EstadoBien::cases();

        return view('inventario.edit', compact('inventario', 'departamentos', 'estados'));
    }

    public function update(UpdateInventarioRequest $request, Inventario $inventario): RedirectResponse
    {
        Gate::authorize('update', $inventario);

        $this->inventarioService->actualizar($inventario, $request->validated());

        return redirect()->route('inventario.index')
            ->with('status', 'Información del bien institucional actualizada exitosamente.');
    }

    public function cambiarEstado(CambiarEstadoInventarioRequest $request, Inventario $inventario): RedirectResponse
    {
        Gate::authorize('cambiarEstado', $inventario);

        $nuevoEstado = EstadoBien::from($request->input('estado'));
        $this->inventarioService->cambiarEstado($inventario, $nuevoEstado, $request->input('motivo'), $request->user());

        return redirect()->route('inventario.index')
            ->with('status', "El estado del bien {$inventario->codigo} se actualizó a {$nuevoEstado->label()}.");
    }

    public function destroy(Request $request, Inventario $inventario): RedirectResponse
    {
        Gate::authorize('delete', $inventario);

        $request->validate([
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'motivo.required' => 'Debe ingresar un motivo para dar de baja el bien.',
        ]);

        $this->inventarioService->anular($inventario, $request->input('motivo'), $request->user());

        return redirect()->route('inventario.index')
            ->with('status', "El bien {$inventario->codigo} ha sido dado de baja.");
    }
}
