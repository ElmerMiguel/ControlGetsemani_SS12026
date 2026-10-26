<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    Informe General de Asamblea
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Consolidado oficial de tesorería y balances por departamento &bull; {{ $datos['cabecera']['periodo_texto'] }}
                </p>
            </div>

            @can('informe_general.exportar')
                <div class="flex items-center gap-2">
                    <a href="{{ route('informe_general.pdf', ['desde' => $desde, 'hasta' => $hasta]) }}"
                       target="_blank"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-semibold shadow-sm transition">
                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        Descargar PDF
                    </a>

                    <a href="{{ route('informe_general.xlsx', ['desde' => $desde, 'hasta' => $hasta]) }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg text-xs font-semibold shadow-sm transition">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exportar Excel (.xlsx)
                    </a>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-6">

        <!-- Filtros de Consulta -->
        <x-ui.card>
            <form method="GET" action="{{ route('informe_general.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div>
                    <label for="desde" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Desde
                    </label>
                    <input type="date" name="desde" id="desde" value="{{ $desde }}"
                           class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>

                <div>
                    <label for="hasta" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Hasta
                    </label>
                    <input type="date" name="hasta" id="hasta" value="{{ $hasta }}"
                           class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>

                <div class="flex items-center gap-2">
                    <x-ui.button type="submit" variant="primary" class="w-full justify-center">
                        Actualizar Informe
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <!-- Advertencias de Auditoría (Fondos con movimientos pero sin cortes aprobados) -->
        @if (!empty($datos['advertencias']))
            <div class="space-y-2">
                @foreach ($datos['advertencias'] as $adv)
                    <div class="p-4 rounded-lg bg-amber-50 border border-amber-300 text-amber-800 flex items-start gap-3 shadow-sm">
                        <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div class="text-xs leading-relaxed">
                            <strong class="font-bold">Advertencia de Auditoría:</strong> {{ $adv['mensaje'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Tarjetas de Resumen Global Consolidado -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <x-ui.card class="bg-slate-50 border-slate-200">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Saldo Inicial</span>
                <div class="text-xl font-bold font-mono text-slate-800 mt-1">
                    {{ formato_moneda($datos['totales']['saldo_inicial']) }}
                </div>
                <span class="text-[11px] text-slate-400">Al inicio del período</span>
            </x-ui.card>

            <x-ui.card class="bg-emerald-50/50 border-emerald-200">
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700">(+) Ingresos Externos</span>
                <div class="text-xl font-bold font-mono text-emerald-700 mt-1">
                    {{ formato_moneda($datos['totales']['ingresos_ordinarios']) }}
                </div>
                <span class="text-[11px] text-emerald-600">Diezmos y ofrendas reales</span>
            </x-ui.card>

            <x-ui.card class="bg-rose-50/50 border-rose-200">
                <span class="text-xs font-semibold uppercase tracking-wider text-rose-700">(-) Egresos Externos</span>
                <div class="text-xl font-bold font-mono text-rose-700 mt-1">
                    {{ formato_moneda($datos['totales']['egresos_ordinarios']) }}
                </div>
                <span class="text-[11px] text-rose-600">Gastos ministeriales</span>
            </x-ui.card>

            <x-ui.card class="bg-indigo-50/50 border-indigo-200">
                <span class="text-xs font-semibold uppercase tracking-wider text-indigo-700">Flujo Neto</span>
                <div class="text-xl font-bold font-mono text-indigo-800 mt-1">
                    {{ formato_moneda($datos['totales']['neto_ordinario']) }}
                </div>
                <span class="text-[11px] text-indigo-600">Ingresos vs egresos</span>
            </x-ui.card>

            <x-ui.card class="bg-sky-50/60 border-sky-300">
                <span class="text-xs font-semibold uppercase tracking-wider text-sky-800">Saldo Final</span>
                <div class="text-xl font-bold font-mono text-sky-900 mt-1">
                    {{ formato_moneda($datos['totales']['saldo_final']) }}
                </div>
                <span class="text-[11px] text-sky-700">Cierre consolidado</span>
            </x-ui.card>
        </div>

        <!-- Tabla Consolidada por Departamento (RN3-03 y RN3-04) -->
        <x-ui.card padding="none">
            <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Estado Consolidado por Departamento</h2>
                    <p class="text-xs text-slate-500">Cálculo basado exclusivamente en cortes de caja aprobados por la Administración General.</p>
                </div>
                <div class="text-xs text-slate-500">
                    <span class="font-bold text-slate-800">{{ $datos['totales']['departamentos_con_info'] }}</span> de {{ $datos['totales']['departamentos_total'] }} departamentos con cortes aprobados
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm text-left">
                    <thead class="bg-slate-50 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="px-4 py-3">Departamento</th>
                            <th scope="col" class="px-4 py-3 text-center">Estado</th>
                            <th scope="col" class="px-4 py-3 text-right">Saldo Inicial</th>
                            <th scope="col" class="px-4 py-3 text-right">(+) Ingresos Ord.</th>
                            <th scope="col" class="px-4 py-3 text-right">(+) Transf. Rec.</th>
                            <th scope="col" class="px-4 py-3 text-right font-bold">(=) Total Ing.</th>
                            <th scope="col" class="px-4 py-3 text-right">(-) Egresos Ord.</th>
                            <th scope="col" class="px-4 py-3 text-right">(-) Transf. Env.</th>
                            <th scope="col" class="px-4 py-3 text-right font-bold">(=) Total Eg.</th>
                            <th scope="col" class="px-4 py-3 text-right font-bold bg-slate-100/50">Saldo Final</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach ($datos['departamentos'] as $depto)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 font-semibold text-slate-900 whitespace-nowrap">
                                    {{ $depto['departamento'] }}
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @if ($depto['tiene_informacion'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800" title="{{ $depto['total_cortes'] }} cortes aprobados en {{ $depto['cajas_con_cortes'] }} caja(s)">
                                            Oficial ({{ $depto['total_cortes'] }})
                                        </span>
                                    @else
                                        <!-- RN3-03: Sin información del período explícita -->
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 italic">
                                            {{ $depto['mensaje'] }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-slate-700 whitespace-nowrap">
                                    {{ $depto['tiene_informacion'] ? formato_moneda($depto['saldo_inicial']) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-emerald-600 whitespace-nowrap">
                                    {{ $depto['tiene_informacion'] ? formato_moneda($depto['ingresos_ordinarios']) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-sky-600 whitespace-nowrap text-xs">
                                    {{ $depto['tiene_informacion'] ? formato_moneda($depto['transferencias_recibidas']) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-emerald-700 whitespace-nowrap">
                                    {{ $depto['tiene_informacion'] ? formato_moneda($depto['total_ingresos']) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-rose-600 whitespace-nowrap">
                                    {{ $depto['tiene_informacion'] ? formato_moneda($depto['egresos_ordinarios']) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-amber-600 whitespace-nowrap text-xs">
                                    {{ $depto['tiene_informacion'] ? formato_moneda($depto['transferencias_enviadas']) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-rose-700 whitespace-nowrap">
                                    {{ $depto['tiene_informacion'] ? formato_moneda($depto['total_egresos']) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-slate-900 bg-slate-50/75 whitespace-nowrap">
                                    {{ $depto['tiene_informacion'] ? formato_moneda($depto['saldo_final']) : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-100/80 border-t-2 border-slate-300 font-bold text-slate-900 text-xs">
                        <tr>
                            <td colspan="2" class="px-4 py-3.5 uppercase tracking-wider">
                                Total General Consolidado
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-sm">
                                {{ formato_moneda($datos['totales']['saldo_inicial']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-sm text-emerald-700">
                                {{ formato_moneda($datos['totales']['ingresos_ordinarios']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-xs text-sky-700">
                                {{ formato_moneda($datos['totales']['transferencias_recibidas']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-sm text-emerald-800">
                                {{ formato_moneda($datos['totales']['total_ingresos']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-sm text-rose-700">
                                {{ formato_moneda($datos['totales']['egresos_ordinarios']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-xs text-amber-700">
                                {{ formato_moneda($datos['totales']['transferencias_enviadas']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-sm text-rose-800">
                                {{ formato_moneda($datos['totales']['total_egresos']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-mono text-base font-extrabold text-slate-900 bg-slate-200/50">
                                {{ formato_moneda($datos['totales']['saldo_final']) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-ui.card>

        <!-- Sección de Conciliación de Movimientos Internos entre Cajas (RN-11 / RN3-04) -->
        <x-ui.card padding="none">
            <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Conciliación de Movimientos Internos entre Cajas (RN-11 / RN3-04)</h2>
                    <p class="text-xs text-slate-500">Transferencias de fondos entre departamentos. Estas transferencias no alteran el patrimonio global de la Iglesia.</p>
                </div>
                <div class="text-xs font-mono font-bold text-slate-700">
                    Total Interno: {{ formato_moneda($datos['transferencias']['total_monto']) }}
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm text-left">
                    <thead class="bg-slate-50 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="px-4 py-3">Fecha</th>
                            <th scope="col" class="px-4 py-3">Caja Origen</th>
                            <th scope="col" class="px-4 py-3">Caja Destino</th>
                            <th scope="col" class="px-4 py-3">Concepto</th>
                            <th scope="col" class="px-4 py-3 text-right">Monto</th>
                            <th scope="col" class="px-4 py-3">Operador</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse ($datos['transferencias']['items'] as $t)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600">
                                    {{ $t->fecha?->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-medium text-slate-900">{{ $t->cajaOrigen?->nombre }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $t->cajaOrigen?->departamento?->nombre }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-medium text-slate-900">{{ $t->cajaDestino?->nombre }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $t->cajaDestino?->departamento?->nombre }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $t->concepto }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right font-mono font-bold text-slate-800">
                                    {{ formato_moneda($t->monto) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-500">
                                    {{ $t->usuario?->name }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-xs text-slate-500">
                                    No se registraron movimientos internos entre cajas durante este período.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
