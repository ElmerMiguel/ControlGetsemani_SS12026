<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Padrón de Aportantes</h1>
                <p class="text-sm text-slate-500">Miembros y donantes registrados para la asignación de ingresos y aportaciones.</p>
            </div>
            @can('aportantes.gestionar')
                <x-ui.button href="{{ route('aportantes.create') }}" variant="primary">
                    <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo Aportante
                </x-ui.button>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filtros y Búsqueda -->
        <x-ui.card>
            <form method="GET" action="{{ route('aportantes.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label for="buscar" class="block text-xs font-medium text-slate-600 mb-1">Buscar por nombre, CUI o teléfono</label>
                    <input
                        type="text"
                        name="buscar"
                        id="buscar"
                        value="{{ request('buscar') }}"
                        placeholder="Ej. Juan Pérez o búsqueda por CUI"
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                    />
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
                    @if (request()->hasAny(['buscar', 'estado']))
                        <a href="{{ route('aportantes.index') }}" class="inline-flex items-center justify-center p-2 text-slate-500 hover:text-slate-800" title="Limpiar filtros">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </div>
            </form>
        </x-ui.card>

        <!-- Tabla de Aportantes -->
        <x-ui.table>
            <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500 border-b border-slate-200">
                <tr>
                    <th scope="col" class="px-6 py-3.5">Nombre Completo</th>
                    <th scope="col" class="px-6 py-3.5">CUI / DPI (Enmascarado)</th>
                    <th scope="col" class="px-6 py-3.5">Teléfono</th>
                    <th scope="col" class="px-6 py-3.5">Estado</th>
                    <th scope="col" class="px-6 py-3.5 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse ($aportantes as $aportante)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-slate-900">
                            {{ $aportante->nombre_completo }}
                        </td>
                        <td class="px-6 py-4 font-mono text-sm text-slate-700">
                            {{ $aportante->cui_enmascarado }}
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            {{ $aportante->telefono ?? '—' }}
                        </td>
                        <td class="px-6 py-4">
                            @if ($aportante->activo)
                                <x-ui.badge variant="success">Activo</x-ui.badge>
                            @else
                                <x-ui.badge variant="danger">Inactivo</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            @can('aportantes.gestionar')
                                <a href="{{ route('aportantes.edit', $aportante) }}" class="text-xs font-medium text-primary-600 hover:text-primary-800">
                                    Editar
                                </a>

                                <form method="POST" action="{{ route('aportantes.estado', $aportante) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button
                                        type="submit"
                                        class="text-xs font-medium {{ $aportante->activo ? 'text-red-600 hover:text-red-800' : 'text-emerald-600 hover:text-emerald-800' }}"
                                        onclick="return confirm('¿Seguro que desea {{ $aportante->activo ? 'desactivar' : 'activar' }} este aportante?')"
                                    >
                                        {{ $aportante->activo ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">
                            No se encontraron aportantes registrados con los criterios especificados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>

        <div class="mt-4">
            {{ $aportantes->links() }}
        </div>
    </div>
</x-app-layout>
