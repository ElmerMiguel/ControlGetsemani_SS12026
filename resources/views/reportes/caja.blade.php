<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    Reporte Financiero por Caja
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    {{ $datos['cabecera']['caja'] }} &bull; {{ $datos['cabecera']['periodo_texto'] }}
                </p>
            </div>

            @can('reportes.exportar')
                <div class="flex items-center gap-2">
                    <a href="{{ route('reportes.caja.pdf', ['caja_id' => $cajaSeleccionada->id, 'desde' => $desde, 'hasta' => $hasta]) }}"
                       target="_blank"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-rose-300 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-lg text-xs font-semibold shadow-sm transition">
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        Descargar PDF
                    </a>

                    <a href="{{ route('reportes.caja.xlsx', ['caja_id' => $cajaSeleccionada->id, 'desde' => $desde, 'hasta' => $hasta]) }}"
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
            <form method="GET" action="{{ route('reportes.caja') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
                @if ($cajas->count() > 1)
                    <div>
                        <label for="caja_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Caja
                        </label>
                        <select name="caja_id" id="caja_id" class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            @foreach ($cajas as $c)
                                <option value="{{ $c->id }}" {{ $c->id == $cajaSeleccionada->id ? 'selected' : '' }}>
                                    {{ $c->codigo }} &bull; {{ $c->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" name="caja_id" value="{{ $cajaSeleccionada->id }}">
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Caja Seleccionada</span>
                        <div class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-md text-sm font-medium text-slate-800">
                            {{ $cajaSeleccionada->codigo }} &bull; {{ $cajaSeleccionada->nombre }}
                        </div>
                    </div>
                @endif

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

                <div>
                    <x-ui.button type="submit" variant="primary" class="w-full justify-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Generar Reporte
                    </x-ui.button>
                </div>
            </form>

            @if ($errors->any())
                <div class="mt-3 p-3 rounded-md bg-rose-50 border border-rose-200 text-rose-700 text-xs">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-ui.card>

        <!-- Resumen Financiero -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <x-ui.card class="bg-white border-l-4 border-slate-400">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Saldo Inicial</span>
                <p class="text-xl font-bold text-slate-900 mt-1">
                    {{ formato_moneda($datos['resumen']['saldo_inicial']) }}
                </p>
                <span class="text-[11px] text-slate-400">Al inicio del período</span>
            </x-ui.card>

            <x-ui.card class="bg-white border-l-4 border-emerald-500">
                <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">(+) Total Ingresos</span>
                <p class="text-xl font-bold text-emerald-700 mt-1">
                    {{ formato_moneda($datos['resumen']['total_ingresos']) }}
                </p>
                <span class="text-[11px] text-slate-400">
                    Ord: {{ formato_moneda($datos['resumen']['ingresos_ordinarios']) }}
                    @if ((float) $datos['resumen']['transferencias_recibidas'] > 0)
                        &bull; Transf: {{ formato_moneda($datos['resumen']['transferencias_recibidas']) }}
                    @endif
                </span>
            </x-ui.card>

            <x-ui.card class="bg-white border-l-4 border-rose-500">
                <span class="text-xs font-semibold text-rose-600 uppercase tracking-wider">(-) Total Egresos</span>
                <p class="text-xl font-bold text-rose-700 mt-1">
                    {{ formato_moneda($datos['resumen']['total_egresos']) }}
                </p>
                <span class="text-[11px] text-slate-400">
                    Ord: {{ formato_moneda($datos['resumen']['egresos_ordinarios']) }}
                    @if ((float) $datos['resumen']['transferencias_enviadas'] > 0)
                        &bull; Transf: {{ formato_moneda($datos['resumen']['transferencias_enviadas']) }}
                    @endif
                </span>
            </x-ui.card>

            <x-ui.card class="bg-white border-l-4 {{ (float) $datos['resumen']['neto_periodo'] >= 0 ? 'border-primary-500' : 'border-amber-500' }}">
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">(=) Flujo Neto</span>
                <p class="text-xl font-bold {{ (float) $datos['resumen']['neto_periodo'] >= 0 ? 'text-primary-700' : 'text-amber-700' }} mt-1">
                    {{ formato_moneda($datos['resumen']['neto_periodo']) }}
                </p>
                <span class="text-[11px] text-slate-400">Ingresos − Egresos</span>
            </x-ui.card>

            <x-ui.card class="bg-sky-50 border-l-4 border-sky-600">
                <span class="text-xs font-semibold text-sky-800 uppercase tracking-wider">(=) Saldo Final</span>
                <p class="text-xl font-bold text-sky-900 mt-1">
                    {{ formato_moneda($datos['resumen']['saldo_final']) }}
                </p>
                <span class="text-[11px] text-sky-600">Al cierre del período</span>
            </x-ui.card>
        </div>

        <!-- Desglose de Cuentas: Ingresos y Egresos -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Ingresos Ordinarios por Cuenta -->
            <x-ui.card>
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        Ingresos Ordinarios por Cuenta
                    </h2>
                    <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-1 rounded">
                        Total: {{ formato_moneda($datos['total_ingresos_cuentas']) }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-slate-600 text-xs uppercase font-semibold">
                            <tr>
                                <th class="px-3 py-2 text-left">Código</th>
                                <th class="px-3 py-2 text-left">Cuenta</th>
                                <th class="px-3 py-2 text-right">Total</th>
                                <th class="px-3 py-2 text-right">%</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($datos['ingresos_por_cuenta'] as $cuenta)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-3 py-2 font-mono text-xs text-slate-500">{{ $cuenta['codigo'] }}</td>
                                    <td class="px-3 py-2 font-medium text-slate-800">{{ $cuenta['nombre'] }}</td>
                                    <td class="px-3 py-2 text-right font-mono font-semibold text-slate-900">
                                        {{ formato_moneda($cuenta['total']) }}
                                    </td>
                                    <td class="px-3 py-2 text-right font-mono text-xs text-slate-500">
                                        {{ number_format($cuenta['porcentaje'], 2) }}%
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-6 text-center text-slate-400 italic">
                                        No hay ingresos ordinarios en el rango seleccionado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (count($datos['ingresos_por_cuenta']) > 0)
                            <tfoot class="bg-slate-50 font-semibold text-slate-900 border-t border-slate-200">
                                <tr>
                                    <td colspan="2" class="px-3 py-2">TOTAL ORDINARIOS</td>
                                    <td class="px-3 py-2 text-right font-mono text-emerald-700">
                                        {{ formato_moneda($datos['total_ingresos_cuentas']) }}
                                    </td>
                                    <td class="px-3 py-2 text-right font-mono text-xs">100.00%</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </x-ui.card>

            <!-- Egresos Ordinarios por Cuenta -->
            <x-ui.card>
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                        Egresos Ordinarios por Cuenta
                    </h2>
                    <span class="text-xs font-bold text-rose-700 bg-rose-50 px-2 py-1 rounded">
                        Total: {{ formato_moneda($datos['total_egresos_cuentas']) }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-slate-600 text-xs uppercase font-semibold">
                            <tr>
                                <th class="px-3 py-2 text-left">Código</th>
                                <th class="px-3 py-2 text-left">Cuenta</th>
                                <th class="px-3 py-2 text-right">Total</th>
                                <th class="px-3 py-2 text-right">%</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($datos['egresos_por_cuenta'] as $cuenta)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-3 py-2 font-mono text-xs text-slate-500">{{ $cuenta['codigo'] }}</td>
                                    <td class="px-3 py-2 font-medium text-slate-800">{{ $cuenta['nombre'] }}</td>
                                    <td class="px-3 py-2 text-right font-mono font-semibold text-slate-900">
                                        {{ formato_moneda($cuenta['total']) }}
                                    </td>
                                    <td class="px-3 py-2 text-right font-mono text-xs text-slate-500">
                                        {{ number_format($cuenta['porcentaje'], 2) }}%
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-6 text-center text-slate-400 italic">
                                        No hay egresos ordinarios en el rango seleccionado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (count($datos['egresos_por_cuenta']) > 0)
                            <tfoot class="bg-slate-50 font-semibold text-slate-900 border-t border-slate-200">
                                <tr>
                                    <td colspan="2" class="px-3 py-2">TOTAL ORDINARIOS</td>
                                    <td class="px-3 py-2 text-right font-mono text-rose-700">
                                        {{ formato_moneda($datos['total_egresos_cuentas']) }}
                                    </td>
                                    <td class="px-3 py-2 text-right font-mono text-xs">100.00%</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </x-ui.card>
        </div>

        <!-- Matriz de Egresos Cuenta × Mes (con scroll horizontal) -->
        <x-ui.card>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-3 border-b border-slate-200 mb-4 gap-2">
                <div>
                    <h2 class="text-base font-bold text-slate-900">
                        Matriz de Egresos: Cuenta &times; Mes
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Distribución mensual por categoría contable (espejo del informe general de asamblea).
                    </p>
                </div>
                <span class="text-xs font-bold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-md">
                    Total Matriz: {{ formato_moneda($datos['matriz_egresos']['total_general']) }}
                </span>
            </div>

            <div class="overflow-x-auto border border-slate-200 rounded-lg">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-800 text-white text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-3 py-2.5 text-left font-semibold sticky left-0 bg-slate-800 z-10">Código</th>
                            <th class="px-3 py-2.5 text-left font-semibold sticky left-14 bg-slate-800 z-10">Cuenta de Egreso</th>
                            @foreach ($datos['matriz_egresos']['meses'] as $mes)
                                <th class="px-3 py-2.5 text-right font-semibold whitespace-nowrap">
                                    {{ $mes['nombre'] }}
                                </th>
                            @endforeach
                            <th class="px-3 py-2.5 text-right font-bold bg-slate-900">Total Cuenta</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($datos['matriz_egresos']['filas'] as $fila)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-3 py-2 font-mono text-xs text-slate-500 sticky left-0 bg-white group-hover:bg-slate-50">
                                    {{ $fila['codigo'] }}
                                </td>
                                <td class="px-3 py-2 font-medium text-slate-800 whitespace-nowrap sticky left-14 bg-white group-hover:bg-slate-50">
                                    {{ $fila['nombre'] }}
                                </td>
                                @foreach ($datos['matriz_egresos']['meses'] as $mes)
                                    <td class="px-3 py-2 text-right font-mono text-xs whitespace-nowrap {{ (float) $fila['valores'][$mes['clave']] > 0 ? 'text-slate-800 font-semibold' : 'text-slate-300' }}">
                                        {{ (float) $fila['valores'][$mes['clave']] > 0 ? formato_moneda($fila['valores'][$mes['clave']]) : '—' }}
                                    </td>
                                @endforeach
                                <td class="px-3 py-2 text-right font-mono font-bold text-slate-900 bg-slate-50 whitespace-nowrap">
                                    {{ formato_moneda($fila['total']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 3 + count($datos['matriz_egresos']['meses']) }}" class="px-3 py-8 text-center text-slate-400 italic">
                                    No se registraron egresos en los meses correspondientes al rango seleccionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-slate-100 font-semibold text-slate-900 border-t-2 border-slate-300">
                        <tr>
                            <td class="px-3 py-2.5 sticky left-0 bg-slate-100 text-xs font-bold uppercase">TOTAL</td>
                            <td class="px-3 py-2.5 sticky left-14 bg-slate-100 text-xs font-bold uppercase">MENSUAL</td>
                            @foreach ($datos['matriz_egresos']['meses'] as $mes)
                                <td class="px-3 py-2.5 text-right font-mono font-bold text-xs whitespace-nowrap text-rose-700">
                                    {{ formato_moneda($datos['matriz_egresos']['totales_mes'][$mes['clave']] ?? '0.00') }}
                                </td>
                            @endforeach
                            <td class="px-3 py-2.5 text-right font-mono font-extrabold text-sm whitespace-nowrap bg-slate-200 text-slate-900">
                                {{ formato_moneda($datos['matriz_egresos']['total_general']) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-ui.card>

        <!-- Transferencias Internas (RN-11) si existen -->
        @if (! empty($datos['transferencias']['recibidas']) || ! empty($datos['transferencias']['enviadas']))
            <x-ui.card>
                <div class="pb-3 border-b border-slate-200 mb-4">
                    <h2 class="text-base font-bold text-slate-900">
                        Transferencias Internas del Período (RN-11)
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Movimientos interbancarios o entre cajas registrados con la cuenta especial de sistema 900.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Transferencias Recibidas -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xs font-bold uppercase text-emerald-700 tracking-wider">
                                Transferencias Recibidas (Entrantes)
                            </h3>
                            <span class="text-xs font-bold font-mono text-emerald-700">
                                {{ formato_moneda($datos['transferencias']['total_recibidas']) }}
                            </span>
                        </div>
                        <div class="overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="min-w-full divide-y divide-slate-200 text-xs">
                                <thead class="bg-slate-50 font-semibold text-slate-600">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Fecha</th>
                                        <th class="px-3 py-2 text-left">Origen</th>
                                        <th class="px-3 py-2 text-left">Concepto</th>
                                        <th class="px-3 py-2 text-right">Monto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse ($datos['transferencias']['recibidas'] as $tr)
                                        <tr>
                                            <td class="px-3 py-2 font-mono whitespace-nowrap text-slate-500">{{ $tr['fecha'] }}</td>
                                            <td class="px-3 py-2 font-medium text-slate-800">{{ $tr['caja_origen'] }}</td>
                                            <td class="px-3 py-2 text-slate-600">{{ $tr['concepto'] }}</td>
                                            <td class="px-3 py-2 text-right font-mono font-semibold text-emerald-700">
                                                {{ formato_moneda($tr['monto']) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-3 py-4 text-center text-slate-400 italic">
                                                Sin transferencias recibidas en el período.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Transferencias Enviadas -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xs font-bold uppercase text-rose-700 tracking-wider">
                                Transferencias Enviadas (Salientes)
                            </h3>
                            <span class="text-xs font-bold font-mono text-rose-700">
                                {{ formato_moneda($datos['transferencias']['total_enviadas']) }}
                            </span>
                        </div>
                        <div class="overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="min-w-full divide-y divide-slate-200 text-xs">
                                <thead class="bg-slate-50 font-semibold text-slate-600">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Fecha</th>
                                        <th class="px-3 py-2 text-left">Destino</th>
                                        <th class="px-3 py-2 text-left">Concepto</th>
                                        <th class="px-3 py-2 text-right">Monto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse ($datos['transferencias']['enviadas'] as $te)
                                        <tr>
                                            <td class="px-3 py-2 font-mono whitespace-nowrap text-slate-500">{{ $te['fecha'] }}</td>
                                            <td class="px-3 py-2 font-medium text-slate-800">{{ $te['caja_destino'] }}</td>
                                            <td class="px-3 py-2 text-slate-600">{{ $te['concepto'] }}</td>
                                            <td class="px-3 py-2 text-right font-mono font-semibold text-rose-700">
                                                {{ formato_moneda($te['monto']) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-3 py-4 text-center text-slate-400 italic">
                                                Sin transferencias enviadas en el período.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        @endif

    </div>
</x-app-layout>
