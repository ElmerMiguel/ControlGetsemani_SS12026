<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Bitácora de Auditoría</h1>
                <p class="text-sm text-slate-500">Registro cronológico inmutable de operaciones, eventos de negocio y autenticación.</p>
            </div>
            <div class="inline-flex items-center text-xs text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
                <svg class="w-4 h-4 text-slate-400 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Registros protegidos contra modificación (RN-16)
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filtros de Auditoría -->
        <x-ui.card>
            <form method="GET" action="{{ route('bitacora.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div class="md:col-span-2">
                    <label for="buscar" class="block text-xs font-medium text-slate-600 mb-1">Buscar en descripción o IP</label>
                    <input
                        type="text"
                        name="buscar"
                        id="buscar"
                        value="{{ request('buscar') }}"
                        placeholder="Ej. Juan Pérez o 192.168.1.1"
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                    />
                </div>

                <div>
                    <label for="usuario_id" class="block text-xs font-medium text-slate-600 mb-1">Usuario</label>
                    <select
                        name="usuario_id"
                        id="usuario_id"
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="">Todos</option>
                        @foreach ($usuarios as $u)
                            <option value="{{ $u->id }}" @selected(request('usuario_id') == $u->id)>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="accion" class="block text-xs font-medium text-slate-600 mb-1">Acción</label>
                    <select
                        name="accion"
                        id="accion"
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="">Todas</option>
                        @foreach ($acciones as $acc)
                            <option value="{{ $acc }}" @selected(request('accion') === $acc)>
                                {{ $acc }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="tabla" class="block text-xs font-medium text-slate-600 mb-1">Tabla</label>
                    <select
                        name="tabla"
                        id="tabla"
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="">Todas</option>
                        @foreach ($tablas as $tbl)
                            <option value="{{ $tbl }}" @selected(request('tabla') === $tbl)>
                                {{ $tbl }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <x-ui.button type="submit" variant="secondary" class="w-full justify-center">
                        Filtrar
                    </x-ui.button>
                    @if (request()->hasAny(['buscar', 'usuario_id', 'accion', 'tabla', 'fecha_desde', 'fecha_hasta']))
                        <a href="{{ route('bitacora.index') }}" class="p-2 text-slate-500 hover:text-slate-800 shrink-0" title="Limpiar filtros">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </div>

                <div class="md:col-span-3">
                    <label for="fecha_desde" class="block text-xs font-medium text-slate-600 mb-1">Desde</label>
                    <input
                        type="date"
                        name="fecha_desde"
                        id="fecha_desde"
                        value="{{ request('fecha_desde') }}"
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                    />
                </div>

                <div class="md:col-span-3">
                    <label for="fecha_hasta" class="block text-xs font-medium text-slate-600 mb-1">Hasta</label>
                    <input
                        type="date"
                        name="fecha_hasta"
                        id="fecha_hasta"
                        value="{{ request('fecha_hasta') }}"
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                    />
                </div>
            </form>
        </x-ui.card>

        <!-- Tabla de Registros -->
        <x-ui.card :padding="false">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Fecha / Hora</th>
                            <th class="px-6 py-3 font-semibold">Usuario</th>
                            <th class="px-6 py-3 font-semibold">Acción</th>
                            <th class="px-6 py-3 font-semibold">Afecta A</th>
                            <th class="px-6 py-3 font-semibold">Descripción</th>
                            <th class="px-6 py-3 font-semibold">IP</th>
                            <th class="px-6 py-3 font-semibold text-right">Detalle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($registros as $reg)
                            @php
                                $badgeVariant = match ($reg->accion) {
                                    'crear' => 'success',
                                    'modificar', 'restaurar' => 'info',
                                    'anular', 'eliminar' => 'danger',
                                    'auth.login' => 'success',
                                    'auth.failed' => 'danger',
                                    'auth.logout' => 'gray',
                                    default => 'purple',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-6 py-3.5 whitespace-nowrap text-xs font-mono text-slate-500">
                                    {{ $reg->fecha_hora->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap">
                                    @if ($reg->usuario)
                                        <div class="font-medium text-slate-800 text-xs">{{ $reg->usuario->name }}</div>
                                        <div class="text-[11px] text-slate-400">{{ $reg->usuario->email }}</div>
                                    @else
                                        <span class="inline-flex items-center text-xs text-slate-500 italic">
                                            Sistema
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap">
                                    <x-ui.badge :variant="$badgeVariant">
                                        {{ $reg->accion }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap text-xs text-slate-600">
                                    @if ($reg->tabla_afectada)
                                        <span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-[11px] text-slate-700">
                                            {{ $reg->tabla_afectada }} #{{ $reg->registro_id }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 text-xs text-slate-700 max-w-xs truncate" title="{{ $reg->descripcion }}">
                                    {{ $reg->descripcion }}
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap text-xs font-mono text-slate-500">
                                    {{ $reg->ip ?? '—' }}
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap text-right text-xs font-medium">
                                    <a
                                        href="{{ route('bitacora.show', $reg) }}"
                                        class="inline-flex items-center text-primary-600 hover:text-primary-800"
                                    >
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-slate-400">
                                    No se encontraron registros de bitácora con los criterios seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($registros->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">
                    {{ $registros->links() }}
                </div>
            @endif
        </x-ui.card>
    </div>
</x-app-layout>
