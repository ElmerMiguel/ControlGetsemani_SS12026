<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Usuarios del Sistema</h1>
                <p class="text-sm text-slate-500">Gestión de cuentas de acceso, roles, cajas y permisos de tesorería.</p>
            </div>
            @can('usuarios.gestionar')
                <x-ui.button href="{{ route('usuarios.create') }}" variant="primary">
                    <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo Usuario
                </x-ui.button>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('temp_password'))
            <div class="p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-lg shadow-sm">
                <div class="flex items-start">
                    <div class="shrink-0 text-amber-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                    </div>
                    <div class="ms-3">
                        <h3 class="text-sm font-semibold text-amber-800">Contraseña temporal generada para {{ session('temp_password_user') }}</h3>
                        <div class="mt-1 text-sm text-amber-700">
                            Copia esta contraseña ahora. Por motivos de seguridad <strong class="underline">no se volverá a mostrar</strong>:
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="inline-block bg-white px-3 py-1.5 border border-amber-300 rounded font-mono text-base font-bold text-slate-800 select-all shadow-sm">
                                {{ session('temp_password') }}
                            </span>
                            <span class="text-xs text-amber-600 italic">El usuario deberá cambiarla obligatoriamente en su primer inicio de sesión.</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Filtros y Búsqueda -->
        <x-ui.card>
            <form method="GET" action="{{ route('usuarios.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label for="buscar" class="block text-xs font-medium text-slate-600 mb-1">Buscar por nombre o correo</label>
                    <input
                        type="text"
                        name="buscar"
                        id="buscar"
                        value="{{ request('buscar') }}"
                        placeholder="Ej. Juan Pérez o juan@getsemani.test"
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                    />
                </div>
                <div>
                    <label for="rol" class="block text-xs font-medium text-slate-600 mb-1">Rol</label>
                    <select
                        name="rol"
                        id="rol"
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="">Todos los roles</option>
                        @foreach ($roles as $rol)
                            <option value="{{ $rol->name }}" @selected(request('rol') === $rol->name)>
                                {{ ucfirst($rol->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <label for="estado" class="block text-xs font-medium text-slate-600 mb-1">Estado</label>
                        <select
                            name="estado"
                            id="estado"
                            class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                        >
                            <option value="">Todos</option>
                            <option value="activo" @selected(request('estado') === 'activo')>Activo</option>
                            <option value="inactivo" @selected(request('estado') === 'inactivo')>Inactivo</option>
                        </select>
                    </div>
                    <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
                    @if (request()->hasAny(['buscar', 'rol', 'estado']))
                        <a href="{{ route('usuarios.index') }}" class="inline-flex items-center justify-center p-2 text-slate-500 hover:text-slate-800" title="Limpiar filtros">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </div>
            </form>
        </x-ui.card>

        <!-- Tabla de Usuarios -->
        <x-ui.card :padding="false">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Usuario</th>
                            <th class="px-6 py-3 font-semibold">Rol</th>
                            <th class="px-6 py-3 font-semibold">Cajas Asignadas</th>
                            <th class="px-6 py-3 font-semibold">Estado</th>
                            <th class="px-6 py-3 font-semibold">Último Acceso</th>
                            <th class="px-6 py-3 font-semibold text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($usuarios as $usuario)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-semibold text-slate-900">{{ $usuario->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $usuario->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @foreach ($usuario->roles as $rol)
                                        <x-ui.badge :variant="$rol->name === 'admin' ? 'purple' : 'info'">
                                            {{ ucfirst($rol->name) }}
                                        </x-ui.badge>
                                    @endforeach
                                </td>
                                <td class="px-6 py-4">
                                    @if ($usuario->hasRole('admin'))
                                        <span class="text-xs text-slate-400 italic">Acceso a todas las cajas</span>
                                    @elseif ($usuario->cajas->isNotEmpty())
                                        <div class="flex flex-wrap gap-1 max-w-xs">
                                            @foreach ($usuario->cajas as $caja)
                                                <x-ui.badge variant="gray">{{ $caja->codigo }} - {{ $caja->nombre }}</x-ui.badge>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-amber-600 font-medium">Sin cajas asignadas</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($usuario->activo)
                                        <x-ui.badge variant="success">Activo</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="danger">Inactivo</x-ui.badge>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                                    {{ $usuario->last_login_at ? $usuario->last_login_at->translatedFormat('d/m/Y H:i') : 'Nunca' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium space-x-2">
                                    <a
                                        href="{{ route('usuarios.edit', $usuario) }}"
                                        class="inline-flex items-center text-primary-600 hover:text-primary-800"
                                    >
                                        Editar
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('usuarios.password', $usuario) }}"
                                        class="inline-block"
                                        onsubmit="return confirm('¿Desea generar una nueva contraseña temporal para {{ $usuario->name }}? La contraseña actual quedará sin efecto.');"
                                    >
                                        @csrf
                                        <button
                                            type="submit"
                                            class="text-amber-600 hover:text-amber-800"
                                            title="Generar nueva clave temporal"
                                        >
                                            Nueva Clave
                                        </button>
                                    </form>

                                    @if (auth()->id() !== $usuario->id)
                                        <form
                                            method="POST"
                                            action="{{ route('usuarios.estado', $usuario) }}"
                                            class="inline-block"
                                            onsubmit="return confirm('¿Seguro que desea {{ $usuario->activo ? 'desactivar' : 'activar' }} a {{ $usuario->name }}?');"
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                type="submit"
                                                class="{{ $usuario->activo ? 'text-red-600 hover:text-red-800' : 'text-emerald-600 hover:text-emerald-800' }}"
                                            >
                                                {{ $usuario->activo ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                                    No se encontraron usuarios registrados con los criterios seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($usuarios->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">
                    {{ $usuarios->links() }}
                </div>
            @endif
        </x-ui.card>
    </div>
</x-app-layout>
