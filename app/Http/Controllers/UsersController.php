<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Departamento;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * UsersController
 *
 * Administra el CRUD de usuarios, asignación de roles, cajas y permisos directos,
 * además de activar/desactivar usuarios y regenerar contraseñas temporales (RN-14).
 */
class UsersController extends Controller
{
    /**
     * Muestra la lista paginada de usuarios con búsqueda y filtros.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $query = User::with(['roles', 'cajas'])->orderBy('name');

        if ($request->filled('buscar')) {
            $buscar = trim($request->input('buscar'));
            $query->where(function ($q) use ($buscar) {
                $q->where('name', 'like', "%{$buscar}%")
                    ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('rol')) {
            $query->role($request->input('rol'));
        }

        if ($request->filled('estado')) {
            $activo = $request->input('estado') === 'activo';
            $query->where('activo', $activo);
        }

        $usuarios = $query->paginate(15)->withQueryString();
        $roles = Role::orderBy('name')->get();

        return view('usuarios.index', compact('usuarios', 'roles'));
    }

    /**
     * Muestra el formulario para crear un nuevo usuario.
     */
    public function create(): View
    {
        Gate::authorize('create', User::class);

        $departamentos = Departamento::with(['cajas' => fn ($q) => $q->where('activa', true)])
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $permisos = Permission::orderBy('name')->get()->groupBy(function ($permiso) {
            return explode('.', $permiso->name)[0];
        });

        return view('usuarios.create', compact('departamentos', 'permisos'));
    }

    /**
     * Almacena un nuevo usuario en la base de datos con contraseña temporal.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $tempPassword = $request->filled('password') ? $request->input('password') : Str::password(12);

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($tempPassword),
            'activo' => true,
            'must_change_password' => true,
        ]);

        $user->assignRole($request->input('rol'));

        if ($request->input('rol') === 'tesorero') {
            $user->cajas()->sync($request->input('cajas', []));
        }

        if ($request->filled('permisos')) {
            $user->syncPermissions($request->input('permisos', []));
        }

        session()->flash('status', 'Usuario creado exitosamente.');
        session()->flash('temp_password', $tempPassword);
        session()->flash('temp_password_user', $user->name);

        return redirect()->route('usuarios.index');
    }

    /**
     * Muestra el formulario para editar un usuario existente.
     */
    public function edit(User $usuario): View
    {
        Gate::authorize('update', $usuario);

        $usuario->load(['roles', 'cajas', 'permissions']);

        $departamentos = Departamento::with(['cajas' => fn ($q) => $q->where('activa', true)])
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $permisos = Permission::orderBy('name')->get()->groupBy(function ($permiso) {
            return explode('.', $permiso->name)[0];
        });

        return view('usuarios.edit', compact('usuario', 'departamentos', 'permisos'));
    }

    /**
     * Actualiza los datos, rol, cajas y permisos de un usuario.
     */
    public function update(UpdateUserRequest $request, User $usuario): RedirectResponse
    {
        $usuario->update([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
        ]);

        $usuario->syncRoles([$request->input('rol')]);

        if ($request->input('rol') === 'tesorero') {
            $usuario->cajas()->sync($request->input('cajas', []));
        } else {
            $usuario->cajas()->detach();
        }

        $usuario->syncPermissions($request->input('permisos', []));

        return redirect()->route('usuarios.index')->with('status', 'Usuario actualizado exitosamente.');
    }

    /**
     * Alterna el estado activo/inactivo de un usuario (RN-14).
     */
    public function cambiarEstado(User $usuario): RedirectResponse
    {
        Gate::authorize('toggleEstado', $usuario);

        $nuevoEstado = ! $usuario->activo;
        $usuario->update(['activo' => $nuevoEstado]);

        $mensaje = $nuevoEstado ? 'Usuario activado exitosamente.' : 'Usuario desactivado exitosamente.';

        return back()->with('status', $mensaje);
    }

    /**
     * Regenera una contraseña temporal para el usuario (RN-14).
     */
    public function generarPassword(User $usuario): RedirectResponse
    {
        Gate::authorize('resetPassword', $usuario);

        $tempPassword = Str::password(12);

        $usuario->update([
            'password' => Hash::make($tempPassword),
            'must_change_password' => true,
        ]);

        return back()->with([
            'status' => 'Se ha generado una nueva contraseña temporal.',
            'temp_password' => $tempPassword,
            'temp_password_user' => $usuario->name,
        ]);
    }
}
