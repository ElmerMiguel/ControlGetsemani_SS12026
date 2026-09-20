<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-slate-900">{{ $caja->nombre }}</h1>
                    <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                        {{ $caja->codigo }}
                    </span>
                    @if ($caja->activa)
                        <x-ui.badge variant="success">Activa</x-ui.badge>
                    @else
                        <x-ui.badge variant="danger">Inactiva</x-ui.badge>
                    @endif
                </div>
                <p class="text-sm text-slate-500 mt-1">
                    {{ $caja->departamento->nombre }} &bull; Custodia en {{ ucfirst($caja->medio->value) }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <x-ui.button href="{{ route('cajas.index') }}" variant="secondary">
                    Volver a Cajas
                </x-ui.button>
                @can('ingresos.crear')
                    @if ($caja->activa)
                        <x-ui.button href="{{ route('ingresos.create', ['caja_id' => $caja->id]) }}" variant="success">
                            + Ingreso
                        </x-ui.button>
                    @endif
                @endcan
                @can('egresos.crear')
                    @if ($caja->activa)
                        <x-ui.button href="{{ route('egresos.create', ['caja_id' => $caja->id]) }}" variant="danger">
                            - Egreso
                        </x-ui.button>
                    @endif
                @endcan
                @can('cajas.gestionar')
                    <x-ui.button href="{{ route('cajas.edit', $caja) }}" variant="primary">
                        Editar Caja
                    </x-ui.button>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @php
            $esNegativo = bccomp((string) $saldoActual, '0.00', 2) < 0;
        @endphp

        <!-- Alerta si saldo es negativo (RN-10) -->
        @if ($esNegativo)
            <div class="rounded-xl border border-danger-200 bg-danger-50 p-4 text-danger-800 flex items-start gap-3">
                <svg class="w-5 h-5 text-danger-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <h3 class="font-bold text-danger-900">Alerta: Saldo Negativo (RN-10)</h3>
                    <p class="text-sm text-danger-700 mt-0.5">
                        El saldo actual de esta caja es negativo debido a egresos acumulados que superan los ingresos disponibles. Se requiere revisión contable inmediata.
                    </p>
                </div>
            </div>
        @endif

        <!-- Tarjetas de Métricas en Tiempo Real (RN-02) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-ui.card class="{{ $esNegativo ? 'border-danger-300 bg-danger-50/40' : '' }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Saldo Actual (RN-02)</span>
                    @if ($esNegativo)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-danger-100 text-danger-700 border border-danger-300">
                            Negativo
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                            En Tiempo Real
                        </span>
                    @endif
                </div>
                <div class="mt-2 text-3xl font-extrabold font-mono tabular-nums {{ $esNegativo ? 'text-danger-600' : 'text-slate-900' }}">
                    {{ formato_moneda($saldoActual) }}
                </div>
                <div class="mt-2 text-xs text-slate-500">
                    Aperturada el {{ $caja->fecha_apertura?->format('d/m/Y') }} con {{ formato_moneda($caja->saldo_apertura) }}
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ingresos del Mes</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ now()->translatedFormat('F Y') }}
                    </span>
                </div>
                <div class="mt-2 text-3xl font-extrabold font-mono tabular-nums text-emerald-600">
                    {{ formato_moneda($totalesMes['ingresos']) }}
                </div>
                <div class="mt-2 text-xs text-slate-400">
                    Entradas operativas externas recibidas
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Egresos del Mes</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">
                        {{ now()->translatedFormat('F Y') }}
                    </span>
                </div>
                <div class="mt-2 text-3xl font-extrabold font-mono tabular-nums text-rose-600">
                    {{ formato_moneda($totalesMes['egresos']) }}
                </div>
                <div class="mt-2 text-xs text-slate-400">
                    Salidas operativas externas registradas
                </div>
            </x-ui.card>
        </div>

        <!-- Información Administrativa de Caja -->
        <x-ui.card>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                <div>
                    <span class="text-xs font-medium text-slate-400 uppercase tracking-wider block">Departamento</span>
                    <span class="font-semibold text-slate-800 text-base mt-1 block">{{ $caja->departamento->nombre }}</span>
                    <span class="text-xs text-slate-500">Tipo: {{ ucfirst($caja->departamento->tipo->value) }}</span>
                </div>
                <div>
                    <span class="text-xs font-medium text-slate-400 uppercase tracking-wider block">Tesoreros Encargados</span>
                    <div class="mt-1 flex flex-wrap gap-1">
                        @forelse ($caja->tesoreros as $tesorero)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-50 text-primary-700 border border-primary-200">
                                {{ $tesorero->name }}
                            </span>
                        @empty
                            <span class="text-xs text-slate-400 italic">Sin tesoreros asignados</span>
                        @endforelse
                    </div>
                </div>
                <div>
                    <span class="text-xs font-medium text-slate-400 uppercase tracking-wider block">Custodia y Tipo de Caja</span>
                    <span class="font-semibold text-slate-800 text-base mt-1 block">En {{ ucfirst($caja->medio->value) }}</span>
                    <span class="text-xs text-slate-500">Operaciones contables de {{ $caja->nombre }}</span>
                </div>
            </div>
        </x-ui.card>

        <!-- Últimos 10 Movimientos -->
        <x-ui.card>
            <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Últimos 10 Movimientos</h2>
                    <p class="text-xs text-slate-500">Ingresos y egresos ordenados cronológicamente por fecha.</p>
                </div>
                <div class="flex gap-2">
                    <x-ui.button href="{{ route('ingresos.index', ['caja_id' => $caja->id]) }}" variant="secondary" size="sm">
                        Ver Ingresos
                    </x-ui.button>
                    <x-ui.button href="{{ route('egresos.index', ['caja_id' => $caja->id]) }}" variant="secondary" size="sm">
                        Ver Egresos
                    </x-ui.button>
                </div>
            </div>

            <div class="overflow-x-auto mt-4">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Cuenta Contable</th>
                            <th class="px-4 py-3">Detalle / Aportante</th>
                            <th class="px-4 py-3 text-right">Monto</th>
                            <th class="px-4 py-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($movimientos as $mov)
                            @php
                                $esIngreso = $mov->tipo_movimiento === 'ingreso';
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($esIngreso)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            + Ingreso
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            - Egreso
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-slate-600 font-mono text-xs">
                                    {{ $mov->fecha?->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="font-mono text-xs text-slate-500 font-bold mr-1">{{ $mov->cuenta?->codigo }}</span>
                                    <span class="text-slate-900 font-medium">{{ $mov->cuenta?->nombre ?? 'N/A' }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    @if ($esIngreso)
                                        <span class="block text-slate-800">{{ $mov->recibo ?? 'Sin recibo' }}</span>
                                        @if ($mov->aportante)
                                            <span class="text-xs text-slate-500">Aportante: {{ $mov->aportante->nombre_completo }}</span>
                                        @endif
                                    @else
                                        <span class="block text-slate-800">{{ Str::limit($mov->descripcion, 50) }}</span>
                                        @if ($mov->referencia)
                                            <span class="text-xs text-slate-500">Ref: {{ $mov->referencia }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right font-mono font-bold tabular-nums {{ $esIngreso ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ $esIngreso ? '+' : '-' }} {{ formato_moneda($mov->monto) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-xs">
                                    @if ($esIngreso)
                                        <a href="{{ route('ingresos.edit', $mov) }}" class="text-primary-600 hover:text-primary-800 font-medium">Ver / Editar</a>
                                    @else
                                        <a href="{{ route('egresos.edit', $mov) }}" class="text-primary-600 hover:text-primary-800 font-medium">Ver / Editar</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-400 italic">
                                    No hay movimientos registrados en esta caja todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <!-- Historial de Cortes de Caja (RN-08, RN-09) -->
        <x-ui.card>
            <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Cortes de Caja</h2>
                    <p class="text-xs text-slate-500">Períodos cerrados y estados de revisión contable.</p>
                </div>
                @can('cortes.solicitar')
                    @if ($caja->activa)
                        <x-ui.button href="{{ route('cortes.create', ['caja_id' => $caja->id]) }}" variant="primary" size="sm">
                            + Solicitar Corte
                        </x-ui.button>
                    @endif
                @endcan
            </div>

            <div class="overflow-x-auto mt-4">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3">Período</th>
                            <th class="px-4 py-3">Saldo Inicial</th>
                            <th class="px-4 py-3">Ingresos</th>
                            <th class="px-4 py-3">Egresos</th>
                            <th class="px-4 py-3">Saldo Final</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($cortes as $corte)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-slate-700">
                                    {{ $corte->periodo_inicio?->format('d/m/Y') }} al {{ $corte->periodo_fin?->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-mono tabular-nums text-slate-800">
                                    {{ formato_moneda($corte->saldo_inicial) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-mono tabular-nums text-emerald-600">
                                    + {{ formato_moneda($corte->total_ingresos) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-mono tabular-nums text-rose-600">
                                    - {{ formato_moneda($corte->total_egresos) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-mono tabular-nums font-bold text-slate-900">
                                    {{ formato_moneda($corte->saldo_final) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <x-ui.badge :variant="$corte->estado->badgeVariant()">
                                        {{ $corte->estado->label() }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-xs">
                                    <a href="{{ route('cortes.show', $corte) }}" class="text-primary-600 hover:text-primary-800 font-semibold">
                                        Detalle &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-400 italic">
                                    Esta caja aún no cuenta con cortes de período registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
