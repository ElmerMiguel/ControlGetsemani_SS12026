<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * BitacoraController
 *
 * Visualización y consulta de auditoría histórica del sistema (RN-16).
 * Módulo de solo lectura protegido por el permiso 'bitacora.ver'.
 */
class BitacoraController extends Controller
{
    /**
     * Muestra el listado de auditoría con filtros avanzados y paginación.
     */
    public function index(Request $request): View
    {
        Gate::authorize('bitacora.ver');

        $query = Bitacora::with('usuario')->orderByDesc('id');

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->input('usuario_id'));
        }

        if ($request->filled('accion')) {
            $query->where('accion', $request->input('accion'));
        }

        if ($request->filled('tabla')) {
            $query->where('tabla_afectada', $request->input('tabla'));
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_hora', '>=', $request->input('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_hora', '<=', $request->input('fecha_hasta'));
        }

        if ($request->filled('buscar')) {
            $buscar = trim($request->input('buscar'));
            $query->where(function ($q) use ($buscar) {
                $q->where('descripcion', 'like', "%{$buscar}%")
                    ->orWhere('ip', 'like', "%{$buscar}%");
            });
        }

        $registros = $query->paginate(25)->withQueryString();

        $usuarios = User::orderBy('name')->get(['id', 'name', 'email']);
        $acciones = Bitacora::select('accion')->distinct()->orderBy('accion')->pluck('accion');
        $tablas = Bitacora::select('tabla_afectada')->whereNotNull('tabla_afectada')->distinct()->orderBy('tabla_afectada')->pluck('tabla_afectada');

        return view('bitacora.index', compact('registros', 'usuarios', 'acciones', 'tablas'));
    }

    /**
     * Muestra el detalle de un registro de auditoría, incluyendo diferencias antes/después.
     */
    public function show(Bitacora $bitacora): View
    {
        Gate::authorize('bitacora.ver');

        $bitacora->load('usuario');

        return view('bitacora.show', compact('bitacora'));
    }
}
