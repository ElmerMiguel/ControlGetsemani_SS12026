<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Cajas Contables</h1>
                <p class="text-sm text-slate-500">Administración y control de cajas chicas y cuentas bancarias por departamento.</p>
            </div>
            @can('cajas.gestionar')
                <x-ui.button href="{{ route('cajas.create') }}" variant="primary">
                    <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nueva Caja
                </x-ui.button>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filtros y Búsqueda -->
        <x-ui.card>
            <form method="GET" action="{{ route('cajas.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="buscar" class="block text-xs font-medium text-slate-600 mb-1">Buscar por nombre o código</label>
                    <input
                        type="text"
                        name="buscar"
                        id="buscar"
                        value="{{ request('buscar') }}"
                        placeholder="Ej. Caja General o CJ-01"
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                    />
                </div>
                <div>
                    <label for="departamento_id" class="block text-xs font-medium text-slate-600 mb-1">Departamento</label>
                    <select
                        name="departamento_id"
                        id="departamento_id"
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="">Todos los departamentos</option>
                        @foreach ($departamentos as $depto)
                            <option value="{{ $depto->id }}" @selected(request('departamento_id') == $depto->id)>
                                {{ $depto->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="medio" class="block text-xs font-medium text-slate-600 mb-1">Medio</label>
                    <select
                        name="medio"
                        id="medio"
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="">Todos</option>
                        @foreach ($medios as $medio)
                            <option value="{{ $medio->value }}" @selected(request('medio') === $medio->value)>
                                {{ ucfirst($medio->value) }}
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
                            <option value="activa" @selected(request('estado') === 'activa')>Activa</option>
                            <option value="inactiva" @selected(request('estado') === 'inactiva')>Inactiva</option>
                        </select>
                    </div>
                    <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
                    @if (request()->hasAny(['buscar', 'departamento_id', 'medio', 'estado']))
                        <a href="{{ route('cajas.index') }}" class="inline-flex items-center justify-center p-2 text-slate-500 hover:text-slate-800" title="Limpiar filtros">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </div>
            </form>
        </x-ui.card>

        <!-- Tabla de Cajas -->
        <x-ui.table>
            <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500 border-b border-slate-200">
                <tr>
                    <th scope="col" class="px-6 py-3.5">Código</th>
                    <th scope="col" class="px-6 py-3.5">Caja</th>
                    <th scope="col" class="px-6 py-3.5">Departamento</th>
                    <th scope="col" class="px-6 py-3.5">Medio</th>
                    <th scope="col" class="px-6 py-3.5 text-right">Saldo Apertura</th>
                    <th scope="col" class="px-6 py-3.5">Tesoreros</th>
                    <th scope="col" class="px-6 py-3.5">Estado</th>
                    <th scope="col" class="px-6 py-3.5 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse ($cajas as $caja)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-mono text-xs font-bold text-slate-700">
                            {{ $caja->codigo }}
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('cajas.show', $caja) }}" class="font-medium text-primary-600 hover:text-primary-800">
                                {{ $caja->nombre }}
                            </a>
                            <div class="text-xs text-slate-400">Apertura: {{ $caja->fecha_apertura?->format('d/m/Y') }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            {{ $caja->departamento->nombre }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $caja->medio->value === 'banco' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                {{ ucfirst($caja->medio->value) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right font-mono text-sm tabular-nums text-slate-800">
                            Q {{ number_format($caja->saldo_apertura, 2) }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1 max-w-xs">
                                @forelse ($caja->tesoreros as $tesorero)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                        {{ $tesorero->name }}
                                    </span>
                                @empty
                                    <span class="text-xs text-slate-400 italic">Sin tesorero asignado</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if ($caja->activa)
                                <x-ui.badge variant="success">Activa</x-ui.badge>
                            @else
                                <x-ui.badge variant="danger">Inactiva</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <a href="{{ route('cajas.show', $caja) }}" class="text-xs font-medium text-slate-600 hover:text-slate-900">
                                Ver
                            </a>

                            @can('cajas.gestionar')
                                <a href="{{ route('cajas.edit', $caja) }}" class="text-xs font-medium text-primary-600 hover:text-primary-800">
                                    Editar
                                </a>

                                <form method="POST" action="{{ route('cajas.estado', $caja) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button
                                        type="submit"
                                        class="text-xs font-medium {{ $caja->activa ? 'text-red-600 hover:text-red-800' : 'text-emerald-600 hover:text-emerald-800' }}"
                                        onclick="return confirm('¿Seguro que desea {{ $caja->activa ? 'desactivar' : 'activar' }} esta caja?')"
                                    >
                                        {{ $caja->activa ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-sm text-slate-500">
                            No se encontraron cajas registradas o asignadas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>

        <div class="mt-4">
            {{ $cajas->links() }}
        </div>
    </div>
</x-app-layout>
