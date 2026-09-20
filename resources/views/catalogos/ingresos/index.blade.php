<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Catálogo de Cuentas de Ingreso</h1>
                <p class="text-sm text-slate-500">Clasificación contable para el registro de aportaciones, ofrendas y donaciones.</p>
            </div>
            @can('catalogos.gestionar')
                <x-ui.button href="{{ route('catalogos.ingresos.create') }}" variant="primary">
                    <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nueva Cuenta de Ingreso
                </x-ui.button>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Navegación entre Catálogos de Ingresos y Egresos -->
        <div class="border-b border-slate-200">
            <nav class="-mb-px flex space-x-6">
                <a href="{{ route('catalogos.ingresos.index') }}" class="border-b-2 border-primary-600 pb-3 px-1 text-sm font-semibold text-primary-600">
                    Cuentas de Ingreso
                </a>
                <a href="{{ route('catalogos.egresos.index') }}" class="border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-slate-500 hover:text-slate-700 hover:border-slate-300">
                    Cuentas de Egreso
                </a>
            </nav>
        </div>

        <!-- Filtros y Búsqueda -->
        <x-ui.card>
            <form method="GET" action="{{ route('catalogos.ingresos.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label for="buscar" class="block text-xs font-medium text-slate-600 mb-1">Buscar por código o nombre</label>
                    <input
                        type="text"
                        name="buscar"
                        id="buscar"
                        value="{{ request('buscar') }}"
                        placeholder="Ej. 001 o Diezmo"
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
                        <a href="{{ route('catalogos.ingresos.index') }}" class="inline-flex items-center justify-center p-2 text-slate-500 hover:text-slate-800" title="Limpiar filtros">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </div>
            </form>
        </x-ui.card>

        <!-- Tabla de Cuentas de Ingreso -->
        <x-ui.table>
            <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500 border-b border-slate-200">
                <tr>
                    <th scope="col" class="px-6 py-3.5 w-32">Código</th>
                    <th scope="col" class="px-6 py-3.5">Nombre de la Cuenta</th>
                    <th scope="col" class="px-6 py-3.5">Tipo / Naturaleza</th>
                    <th scope="col" class="px-6 py-3.5">Estado</th>
                    <th scope="col" class="px-6 py-3.5 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse ($cuentas as $cuenta)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-mono text-sm font-bold text-slate-700">
                            {{ $cuenta->codigo }}
                        </td>
                        <td class="px-6 py-4 font-medium text-slate-900">
                            {{ $cuenta->nombre }}
                        </td>
                        <td class="px-6 py-4">
                            @if ($cuenta->es_transferencia)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200">
                                    Cuenta de Sistema (Transferencias)
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                    Operativa ordinaria
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if ($cuenta->activo)
                                <x-ui.badge variant="success">Activa</x-ui.badge>
                            @else
                                <x-ui.badge variant="danger">Inactiva</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            @can('catalogos.gestionar')
                                @if ($cuenta->es_transferencia)
                                    <span class="text-xs text-slate-400 italic" title="Las cuentas de transferencia son reservadas por el sistema (RN-12)">
                                        Protegida
                                    </span>
                                @else
                                    <a href="{{ route('catalogos.ingresos.edit', $cuenta) }}" class="text-xs font-medium text-primary-600 hover:text-primary-800">
                                        Editar
                                    </a>

                                    <form method="POST" action="{{ route('catalogos.ingresos.estado', $cuenta) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            class="text-xs font-medium {{ $cuenta->activo ? 'text-red-600 hover:text-red-800' : 'text-emerald-600 hover:text-emerald-800' }}"
                                            onclick="return confirm('¿Seguro que desea {{ $cuenta->activo ? 'desactivar' : 'activar' }} esta cuenta?')"
                                        >
                                            {{ $cuenta->activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                @endif
                            @else
                                <span class="text-xs text-slate-400">Solo lectura</span>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">
                            No se encontraron cuentas de ingreso registradas con los filtros indicados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>

        <div class="mt-4">
            {{ $cuentas->links() }}
        </div>
    </div>
</x-app-layout>
