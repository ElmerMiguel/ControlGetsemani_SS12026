<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Inventario Institucional</h1>
                <p class="text-sm text-slate-500">Control físico y estado de bienes, instrumentos y activos por departamento (RF-09).</p>
            </div>
            @can('inventario.crear')
                <x-ui.button href="{{ route('inventario.create') }}" variant="primary">
                    <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Registrar Bien
                </x-ui.button>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Resumen de Métricas de Inventario -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-ui.stat
                label="Total de Bienes"
                :value="$conteoPorEstado['total']"
                description="Activos registrados"
            />
            <x-ui.stat
                label="En Buen Estado"
                :value="$conteoPorEstado['correcto']"
                description="Listos para uso"
                variant="success"
            />
            <x-ui.stat
                label="Bienes Dañados"
                :value="$conteoPorEstado['danado']"
                description="Requieren reparación"
                variant="warning"
            />
            <x-ui.stat
                label="Perdidos / Hurto"
                :value="$conteoPorEstado['perdido']"
                description="Dados de baja o extraviados"
                variant="danger"
            />
        </div>

        <!-- Filtros y Búsqueda -->
        <x-ui.card>
            <form method="GET" action="{{ route('inventario.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label for="buscar" class="block text-xs font-medium text-slate-600 mb-1">Buscar por código o nombre</label>
                    <input
                        type="text"
                        name="buscar"
                        id="buscar"
                        value="{{ request('buscar') }}"
                        placeholder="Ej. ACT-001 o Teclado Yamaha"
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
                        @foreach ($departamentos as $dep)
                            <option value="{{ $dep->id }}" @selected((string) request('departamento_id') === (string) $dep->id)>
                                {{ $dep->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="estado" class="block text-xs font-medium text-slate-600 mb-1">Estado</label>
                    <div class="flex gap-2">
                        <select
                            name="estado"
                            id="estado"
                            class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                        >
                            <option value="">Todos los estados</option>
                            @foreach ($estados as $est)
                                <option value="{{ $est->value }}" @selected(request('estado') === $est->value)>
                                    {{ $est->label() }}
                                </option>
                            @endforeach
                        </select>
                        <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
                        @if(request()->hasAny(['buscar', 'departamento_id', 'estado']))
                            <x-ui.button href="{{ route('inventario.index') }}" variant="secondary">Limpiar</x-ui.button>
                        @endif
                    </div>
                </div>
            </form>
        </x-ui.card>

        <!-- Tabla de Bienes -->
        <x-ui.card>
            @if ($bienes->isEmpty())
                <div class="text-center py-12 text-slate-500">
                    <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <p class="mt-4 text-base font-medium text-slate-900">No se encontraron bienes registrados</p>
                    <p class="mt-1 text-sm text-slate-500">Registra un bien o ajusta los filtros aplicados.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/75 text-xs font-semibold text-slate-600">
                                <th class="py-3 px-4">Código</th>
                                <th class="py-3 px-4">Bien / Descripción</th>
                                <th class="py-3 px-4">Departamento</th>
                                <th class="py-3 px-4">Estado</th>
                                <th class="py-3 px-4 text-right">Valor Estimado</th>
                                <th class="py-3 px-4">Fecha Registro</th>
                                <th class="py-3 px-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($bienes as $bien)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-3 px-4 font-mono font-semibold text-slate-900">
                                        {{ $bien->codigo }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-slate-900">{{ $bien->nombre }}</div>
                                        @if ($bien->descripcion)
                                            <div class="text-xs text-slate-500 truncate max-w-xs">{{ $bien->descripcion }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-slate-700">
                                        {{ $bien->departamento->nombre }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <x-ui.badge :variant="$bien->estado->badgeVariant()">
                                            {{ $bien->estado->label() }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono text-slate-700">
                                        {{ $bien->valor_estimado ? 'Q ' . number_format($bien->valor_estimado, 2) : '—' }}
                                    </td>
                                    <td class="py-3 px-4 text-slate-600">
                                        {{ $bien->fecha_registro->format('d/m/Y') }}
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end gap-2" x-data="{ openEstado: false, openBaja: false }">
                                            @can('inventario.cambiar_estado')
                                                <button
                                                    type="button"
                                                    @click="openEstado = true"
                                                    title="Cambiar estado"
                                                    class="text-xs text-amber-600 hover:text-amber-800 font-medium"
                                                >
                                                    Estado
                                                </button>

                                                <!-- Modal Cambiar Estado -->
                                                <div
                                                    x-show="openEstado"
                                                    class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 flex items-center justify-center p-4 text-left"
                                                    x-cloak
                                                >
                                                    <div @click.away="openEstado = false" class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl">
                                                        <h3 class="text-lg font-bold text-slate-900 mb-2">Cambiar Estado: {{ $bien->codigo }}</h3>
                                                        <p class="text-xs text-slate-500 mb-4">{{ $bien->nombre }} ({{ $bien->departamento->nombre }})</p>

                                                        <form method="POST" action="{{ route('inventario.estado', $bien) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <div class="space-y-4">
                                                                <div>
                                                                    <label class="block text-xs font-medium text-slate-700 mb-1">Nuevo Estado</label>
                                                                    <select name="estado" required class="w-full text-sm rounded-lg border-slate-300">
                                                                        @foreach ($estados as $est)
                                                                            <option value="{{ $est->value }}" @selected($bien->estado->value === $est->value)>
                                                                                {{ $est->label() }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div>
                                                                    <label class="block text-xs font-medium text-slate-700 mb-1">Motivo del Cambio (para auditoría)</label>
                                                                    <textarea name="motivo" rows="3" required placeholder="Explica la razón del cambio de estado..." class="w-full text-sm rounded-lg border-slate-300"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="mt-6 flex justify-end gap-2">
                                                                <x-ui.button type="button" variant="secondary" @click="openEstado = false">Cancelar</x-ui.button>
                                                                <x-ui.button type="submit" variant="primary">Guardar Cambio</x-ui.button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endcan

                                            @can('inventario.editar')
                                                <a href="{{ route('inventario.edit', $bien) }}" class="text-xs text-primary-600 hover:text-primary-800 font-medium">
                                                    Editar
                                                </a>
                                            @endcan

                                            @can('inventario.eliminar')
                                                <button
                                                    type="button"
                                                    @click="openBaja = true"
                                                    class="text-xs text-rose-600 hover:text-rose-800 font-medium"
                                                >
                                                    Baja
                                                </button>

                                                <!-- Modal Dar de Baja (SoftDelete con Motivo) -->
                                                <div
                                                    x-show="openBaja"
                                                    class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 flex items-center justify-center p-4 text-left"
                                                    x-cloak
                                                >
                                                    <div @click.away="openBaja = false" class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl">
                                                        <h3 class="text-lg font-bold text-rose-900 mb-2">Dar de Baja: {{ $bien->codigo }}</h3>
                                                        <p class="text-xs text-slate-500 mb-4">RN3-01: El bien no se eliminará físicamente; se registrará su anulación y motivo en bitácora.</p>

                                                        <form method="POST" action="{{ route('inventario.destroy', $bien) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <div>
                                                                <label class="block text-xs font-medium text-slate-700 mb-1">Motivo de Baja / Anulación</label>
                                                                <textarea name="motivo" rows="3" required placeholder="Ej. Pérdida irreparable, robo o destrucción..." class="w-full text-sm rounded-lg border-slate-300"></textarea>
                                                            </div>
                                                            <div class="mt-6 flex justify-end gap-2">
                                                                <x-ui.button type="button" variant="secondary" @click="openBaja = false">Cancelar</x-ui.button>
                                                                <x-ui.button type="submit" variant="danger">Confirmar Baja</x-ui.button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $bienes->links() }}
                </div>
            @endif
        </x-ui.card>
    </div>
</x-app-layout>
