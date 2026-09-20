<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Departamentos</h1>
                <p class="text-sm text-slate-500">Estructura organizacional de la iglesia (consejos, comités, congregaciones y juntas).</p>
            </div>
            @can('departamentos.gestionar')
                <x-ui.button href="{{ route('departamentos.create') }}" variant="primary">
                    <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo Departamento
                </x-ui.button>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filtros y Búsqueda -->
        <x-ui.card>
            <form method="GET" action="{{ route('departamentos.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label for="buscar" class="block text-xs font-medium text-slate-600 mb-1">Buscar por nombre o descripción</label>
                    <input
                        type="text"
                        name="buscar"
                        id="buscar"
                        value="{{ request('buscar') }}"
                        placeholder="Ej. Consejo de Ancianos o Femenil"
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                    />
                </div>
                <div>
                    <label for="tipo" class="block text-xs font-medium text-slate-600 mb-1">Tipo</label>
                    <select
                        name="tipo"
                        id="tipo"
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="">Todos los tipos</option>
                        @foreach ($tipos as $tipo)
                            <option value="{{ $tipo->value }}" @selected(request('tipo') === $tipo->value)>
                                {{ ucfirst($tipo->value) }}
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
                    @if (request()->hasAny(['buscar', 'tipo', 'estado']))
                        <a href="{{ route('departamentos.index') }}" class="inline-flex items-center justify-center p-2 text-slate-500 hover:text-slate-800" title="Limpiar filtros">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </div>
            </form>
        </x-ui.card>

        <!-- Listado de Departamentos -->
        <x-ui.table>
            <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500 border-b border-slate-200">
                <tr>
                    <th scope="col" class="px-6 py-3.5">Nombre</th>
                    <th scope="col" class="px-6 py-3.5">Tipo</th>
                    <th scope="col" class="px-6 py-3.5 text-center">Cajas</th>
                    <th scope="col" class="px-6 py-3.5">Estado</th>
                    <th scope="col" class="px-6 py-3.5 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse ($departamentos as $depto)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-medium text-slate-900">{{ $depto->nombre }}</div>
                            @if ($depto->descripcion)
                                <div class="text-xs text-slate-500 truncate max-w-md">{{ $depto->descripcion }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <x-ui.badge variant="info">
                                {{ ucfirst($depto->tipo->value) }}
                            </x-ui.badge>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                {{ $depto->cajas_count }} {{ $depto->cajas_count === 1 ? 'caja' : 'cajas' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if ($depto->activo)
                                <x-ui.badge variant="success">Activo</x-ui.badge>
                            @else
                                <x-ui.badge variant="danger">Inactivo</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            @can('departamentos.gestionar')
                                <a href="{{ route('departamentos.edit', $depto) }}" class="text-xs font-medium text-primary-600 hover:text-primary-800">
                                    Editar
                                </a>

                                <form method="POST" action="{{ route('departamentos.estado', $depto) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button
                                        type="submit"
                                        class="text-xs font-medium {{ $depto->activo ? 'text-red-600 hover:text-red-800' : 'text-emerald-600 hover:text-emerald-800' }}"
                                        onclick="return confirm('¿Seguro que desea {{ $depto->activo ? 'desactivar' : 'activar' }} este departamento?')"
                                    >
                                        {{ $depto->activo ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">
                            No se encontraron departamentos registrados con los criterios seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>

        <div class="mt-4">
            {{ $departamentos->links() }}
        </div>
    </div>
</x-app-layout>
