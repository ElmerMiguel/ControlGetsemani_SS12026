<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Egresos y Desembolsos</h1>
                <p class="text-sm text-slate-500">Registro y control de gastos operativos y compras autorizadas.</p>
            </div>
            @can('egresos.crear')
                <x-ui.button href="{{ route('egresos.create') }}" variant="primary">
                    <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo Egreso
                </x-ui.button>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-6" x-data="{ anularModalOpen: false, anularAction: '', anularId: '' }">
        <!-- Filtros y Búsqueda -->
        <x-ui.card>
            <form method="GET" action="{{ route('egresos.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label for="caja_id" class="block text-xs font-medium text-slate-600 mb-1">Caja</label>
                    <select
                        name="caja_id"
                        id="caja_id"
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="">Todas las cajas</option>
                        @foreach ($cajas as $caja)
                            <option value="{{ $caja->id }}" @selected(request('caja_id', session('caja_activa_id')) == $caja->id)>
                                {{ $caja->codigo }} - {{ $caja->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="cuenta_id" class="block text-xs font-medium text-slate-600 mb-1">Cuenta de Egreso</label>
                    <select
                        name="cuenta_id"
                        id="cuenta_id"
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="">Todas las cuentas</option>
                        @foreach ($cuentas as $cuenta)
                            <option value="{{ $cuenta->id }}" @selected(request('cuenta_id') == $cuenta->id)>
                                {{ $cuenta->codigo }} &bull; {{ $cuenta->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="fecha_desde" class="block text-xs font-medium text-slate-600 mb-1">Desde</label>
                    <input
                        type="date"
                        name="fecha_desde"
                        id="fecha_desde"
                        value="{{ request('fecha_desde') }}"
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                    />
                </div>

                <div>
                    <label for="fecha_hasta" class="block text-xs font-medium text-slate-600 mb-1">Hasta</label>
                    <input
                        type="date"
                        name="fecha_hasta"
                        id="fecha_hasta"
                        value="{{ request('fecha_hasta') }}"
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                    />
                </div>

                <div>
                    <label for="buscar" class="block text-xs font-medium text-slate-600 mb-1">Buscar</label>
                    <input
                        type="text"
                        name="buscar"
                        id="buscar"
                        value="{{ request('buscar') }}"
                        placeholder="Descripción, factura o ref..."
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                    />
                </div>

                <div class="md:col-span-5 flex flex-wrap items-center justify-between gap-4 pt-2 border-t border-slate-100">
                    <div>
                        @can('cajas.gestionar')
                            <label class="inline-flex items-center text-xs text-slate-600 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="anulados"
                                    value="1"
                                    @checked(request()->boolean('anulados'))
                                    class="rounded border-slate-300 text-primary-600 focus:ring-primary-500 w-4 h-4 me-2"
                                />
                                Incluir movimientos anulados
                            </label>
                        @endcan
                    </div>

                    <div class="flex items-center gap-2">
                        <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
                        @if (request()->hasAny(['caja_id', 'cuenta_id', 'fecha_desde', 'fecha_hasta', 'buscar', 'anulados']))
                            <a href="{{ route('egresos.index') }}" class="inline-flex items-center justify-center p-2 text-slate-500 hover:text-slate-800" title="Limpiar filtros">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </x-ui.card>

        <!-- Tabla de Egresos con Fila de Total SQL -->
        <x-ui.table>
            <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500 border-b border-slate-200">
                <tr>
                    <th scope="col" class="px-4 py-3.5 w-10 text-center"></th>
                    <th scope="col" class="px-4 py-3.5">Fecha</th>
                    <th scope="col" class="px-4 py-3.5">Caja</th>
                    <th scope="col" class="px-4 py-3.5">Cuenta</th>
                    <th scope="col" class="px-4 py-3.5">Descripción</th>
                    <th scope="col" class="px-4 py-3.5">Referencia</th>
                    <th scope="col" class="px-4 py-3.5 text-right">Monto</th>
                    <th scope="col" class="px-4 py-3.5">Registrado por</th>
                    <th scope="col" class="px-4 py-3.5 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
                @forelse ($egresos as $egreso)
                    @php
                        $estaBloqueado = $egreso->esBloqueado();
                        $estaAnulado = $egreso->trashed();
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors {{ $estaAnulado ? 'bg-red-50/50 opacity-75' : '' }}">
                        <td class="px-4 py-4 text-center">
                            @if ($estaBloqueado)
                                <span title="Periodo bloqueado por corte de caja (RN-05/RN-06)" class="text-amber-500 inline-block">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-700">
                            {{ $egreso->fecha?->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-slate-800">
                            {{ $egreso->caja->codigo }}
                        </td>
                        <td class="px-4 py-4 text-sm text-slate-800">
                            <span class="font-mono text-xs text-slate-500">{{ $egreso->cuenta->codigo }}</span>
                            {{ $egreso->cuenta->nombre }}
                        </td>
                        <td class="px-4 py-4 text-sm text-slate-900 max-w-xs truncate">
                            {{ $egreso->descripcion }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-xs font-mono text-slate-600">
                            {{ $egreso->referencia ?? '—' }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-right font-mono text-sm font-bold tabular-nums {{ $estaAnulado ? 'text-red-700 line-through' : 'text-red-600' }}">
                            {{ formato_moneda($egreso->monto) }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-xs text-slate-500">
                            {{ $egreso->usuario->name }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-right space-x-2">
                            @if ($estaAnulado)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800" title="Motivo: {{ $egreso->motivo_anulacion }}">
                                    Anulado
                                </span>
                            @elseif ($estaBloqueado)
                                <span class="text-xs text-slate-400 italic">Bloqueado</span>
                            @else
                                @can('update', $egreso)
                                    <a href="{{ route('egresos.edit', $egreso) }}" class="text-xs font-medium text-primary-600 hover:text-primary-800">
                                        Editar
                                    </a>
                                @endcan

                                @can('anular', $egreso)
                                    <button
                                        type="button"
                                        @click="anularModalOpen = true; anularId = '{{ $egreso->id }}'; anularAction = '{{ route('egresos.anular', $egreso) }}'"
                                        class="text-xs font-medium text-red-600 hover:text-red-800"
                                    >
                                        Anular
                                    </button>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-sm text-slate-500">
                            No se encontraron egresos registrados con los criterios de búsqueda.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <!-- Fila de Total SQL (RN-01) -->
            <tfoot class="bg-slate-50 border-t-2 border-slate-300 font-bold text-slate-900">
                <tr>
                    <td colspan="6" class="px-6 py-3.5 text-right uppercase text-xs tracking-wider">
                        Total Filtrado:
                    </td>
                    <td class="px-4 py-3.5 text-right font-mono text-base text-red-700 tabular-nums">
                        {{ formato_moneda($totalFiltrado) }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </x-ui.table>

        <div class="mt-4">
            {{ $egresos->links() }}
        </div>

        <!-- Modal de Anulación con Motivo Obligatorio (RN-07) -->
        <div
            x-show="anularModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;"
        >
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="anularModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900 bg-opacity-60"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div
                    x-show="anularModalOpen"
                    x-transition
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6 space-y-4"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Anular Egreso Contable</h3>
                            <p class="text-xs text-slate-500">Esta acción no elimina el registro físico pero lo excluye de saldos y cortes.</p>
                        </div>
                    </div>

                    <form :action="anularAction" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="motivo_egreso" class="block text-sm font-medium text-slate-700 mb-1">
                                Motivo de la anulación * (mínimo 10 caracteres)
                            </label>
                            <textarea
                                name="motivo"
                                id="motivo_egreso"
                                rows="3"
                                required
                                minlength="10"
                                placeholder="Indica detalladamente por qué se anula este egreso..."
                                class="w-full text-sm rounded-md border-slate-300 focus:border-red-500 focus:ring-red-500"
                            ></textarea>
                            <p class="mt-1 text-xs text-slate-500">Este motivo quedará auditado en la bitácora del sistema (RN-07).</p>
                        </div>

                        <div class="flex justify-end gap-3 pt-3 border-t border-slate-200">
                            <button
                                type="button"
                                @click="anularModalOpen = false"
                                class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 shadow-sm"
                            >
                                Confirmar Anulación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
