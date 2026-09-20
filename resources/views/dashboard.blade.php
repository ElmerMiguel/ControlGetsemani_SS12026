<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Panel de Control</h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    Bienvenido, <span class="font-medium text-slate-800">{{ $user->name }}</span> &bull; 
                    Rol: <span class="font-semibold text-primary-600">{{ $esAdmin ? 'Administrador General' : 'Tesorero de Departamento' }}</span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 me-2 animate-pulse"></span>
                    En tiempo real (Año {{ $anioActual }})
                </span>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if ($esAdmin)
            <!-- ==================== VISTA ADMINISTRADOR GENERAL ==================== -->

            <!-- Alerta de Cajas con Saldo Negativo (RN-10) -->
            @if (count($cajasNegativas) > 0)
                <div class="rounded-xl border border-danger-300 bg-danger-50 p-5 text-danger-900 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="p-2 rounded-lg bg-danger-100 text-danger-700 mt-0.5">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-bold text-danger-900">
                                Alerta Contable: {{ count($cajasNegativas) }} {{ count($cajasNegativas) === 1 ? 'caja presenta' : 'cajas presentan' }} saldo negativo (RN-10)
                            </h3>
                            <p class="text-xs text-danger-700 mt-1">
                                Las siguientes cajas tienen un saldo acumulado menor a cero debido a egresos que sobrepasaron los fondos disponibles:
                            </p>
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach ($cajasNegativas as $item)
                                    <div class="bg-white/90 border border-danger-200 rounded-lg p-3 flex items-center justify-between shadow-xs">
                                        <div>
                                            <a href="{{ route('cajas.show', $item['caja']) }}" class="font-bold text-danger-800 hover:underline text-sm block">
                                                {{ $item['caja']->nombre }}
                                            </a>
                                            <span class="text-xs text-slate-500 font-mono">{{ $item['caja']->codigo }}</span>
                                        </div>
                                        <span class="font-mono font-bold text-danger-700 text-sm">
                                            {{ formato_moneda($item['saldo']) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Tarjetas de Métricas Globales -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-ui.stat
                    label="Saldo Global Consolidado"
                    :value="formato_moneda($saldoTotal)"
                    subtext="Total disponible en todas las cajas"
                    :color="bccomp((string)$saldoTotal, '0.00', 2) < 0 ? 'danger' : 'primary'"
                />
                <x-ui.stat
                    label="Ingresos del Mes (Iglesia)"
                    :value="formato_moneda($totalesMes['ingresos'])"
                    :subtext="now()->translatedFormat('F Y') . ' (externos)'"
                    color="success"
                />
                <x-ui.stat
                    label="Egresos del Mes (Iglesia)"
                    :value="formato_moneda($totalesMes['egresos'])"
                    :subtext="now()->translatedFormat('F Y') . ' (externos)'"
                    color="danger"
                />
                <a href="{{ route('cajas.index') }}" class="block focus:outline-none">
                    <x-ui.stat
                        label="Cortes Pendientes"
                        :value="$cortesPendientesCount"
                        subtext="Requieren revisión de administración"
                        color="sky"
                    />
                </a>
            </div>

            <!-- Gráfica Mensual Global de Toda la Iglesia -->
            <x-ui.card>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-200 gap-2">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Flujo Mensual Global {{ $anioActual }}</h2>
                        <p class="text-xs text-slate-500">Comparativa consolidada de ingresos y egresos externos mes a mes.</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-semibold bg-slate-100 text-slate-700">
                        Consolidado Todas las Cajas
                    </span>
                </div>
                <div class="pt-4">
                    <x-charts.barras
                        id="chart-admin-global"
                        :labels="$chartData['labels']"
                        :datasets="$chartData['datasets']"
                        height="320"
                    />
                </div>
            </x-ui.card>

            <!-- Saldos por Departamento -->
            <x-ui.card>
                <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Saldos por Departamento</h2>
                        <p class="text-xs text-slate-500">Distribución de fondos acumulados por consejo, comité y congregación.</p>
                    </div>
                    <x-ui.button href="{{ route('departamentos.index') }}" variant="secondary" size="sm">
                        Ver Departamentos
                    </x-ui.button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                    @foreach ($saldosPorDepto as $depto)
                        @php
                            $deptoNegativo = bccomp((string)$depto['saldo'], '0.00', 2) < 0;
                        @endphp
                        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-slate-50 transition-colors">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-bold text-slate-800 text-sm leading-snug">{{ $depto['nombre'] }}</h3>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 font-medium whitespace-nowrap">
                                    {{ $depto['cajas_count'] }} {{ $depto['cajas_count'] === 1 ? 'caja' : 'cajas' }}
                                </span>
                            </div>
                            <div class="mt-3 text-xl font-extrabold font-mono tabular-nums {{ $deptoNegativo ? 'text-danger-600' : 'text-slate-900' }}">
                                {{ formato_moneda($depto['saldo']) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            <!-- Últimos Movimientos Globales -->
            <x-ui.card>
                <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Últimos Movimientos Registrados</h2>
                        <p class="text-xs text-slate-500">Actividad reciente en todas las cajas de la iglesia.</p>
                    </div>
                    <div class="flex gap-2">
                        <x-ui.button href="{{ route('ingresos.index') }}" variant="secondary" size="sm">Ingresos</x-ui.button>
                        <x-ui.button href="{{ route('egresos.index') }}" variant="secondary" size="sm">Egresos</x-ui.button>
                    </div>
                </div>

                <div class="overflow-x-auto mt-4">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Tipo</th>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Caja</th>
                                <th class="px-4 py-3">Cuenta Contable</th>
                                <th class="px-4 py-3 text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($ultimosMovimientos as $mov)
                                @php $esIng = $mov->tipo_movimiento === 'ingreso'; @endphp
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ $esIng ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                            {{ $esIng ? '+ Ingreso' : '- Egreso' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap font-mono text-xs text-slate-600">
                                        {{ $mov->fecha?->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap font-medium text-slate-800 text-xs">
                                        {{ $mov->caja?->nombre }}
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-slate-700 text-xs">
                                        <span class="font-mono text-slate-500 font-bold mr-1">{{ $mov->cuenta?->codigo }}</span>
                                        {{ $mov->cuenta?->nombre }}
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-right font-mono font-bold tabular-nums {{ $esIng ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ $esIng ? '+' : '-' }} {{ formato_moneda($mov->monto) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-400 italic">Sin movimientos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

        @else
            <!-- ==================== VISTA TESORERO DE DEPARTAMENTO ==================== -->

            @if ($cajaActiva)
                <!-- Tarjetas de Métricas de la Caja Activa -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-ui.stat
                        label="Saldo: {{ $cajaActiva->nombre }}"
                        :value="formato_moneda($saldoCajaActiva)"
                        subtext="Saldo en tiempo real (Caja Activa)"
                        :color="bccomp((string)$saldoCajaActiva, '0.00', 2) < 0 ? 'danger' : 'primary'"
                    />
                    <x-ui.stat
                        label="Ingresos del Mes"
                        :value="formato_moneda($totalesMes['ingresos'])"
                        :subtext="now()->translatedFormat('F Y')"
                        color="success"
                    />
                    <x-ui.stat
                        label="Egresos del Mes"
                        :value="formato_moneda($totalesMes['egresos'])"
                        :subtext="now()->translatedFormat('F Y')"
                        color="danger"
                    />
                    <x-ui.stat
                        label="Mis Cajas Asignadas"
                        :value="$cajasAsignadas->count()"
                        subtext="Bajo tu custodia contable"
                        color="sky"
                    />
                </div>
            @endif

            <!-- Lista de Cajas Asignadas con Saldo Actual -->
            <x-ui.card>
                <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Mis Cajas Asignadas</h2>
                        <p class="text-xs text-slate-500">Haz clic en una caja para seleccionarla como caja activa de trabajo.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                    @forelse ($cajasAsignadas as $caja)
                        @php
                            $esActiva = $cajaActiva && $cajaActiva->id === $caja->id;
                            $esNeg = bccomp((string)$caja->saldo_calculado, '0.00', 2) < 0;
                        @endphp
                        <div class="p-4 rounded-xl border {{ $esActiva ? 'border-primary-400 bg-primary-50/30 ring-2 ring-primary-500/20' : 'border-slate-200 bg-white' }} shadow-xs hover:shadow-sm transition-all flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                                        {{ $caja->codigo }}
                                    </span>
                                    @if ($esActiva)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-primary-100 text-primary-700">
                                            Activa
                                        </span>
                                    @endif
                                </div>
                                <h3 class="font-bold text-slate-900 text-base mt-2">{{ $caja->nombre }}</h3>
                                <p class="text-xs text-slate-500 mt-0.5">{{ $caja->departamento->nombre }}</p>
                            </div>

                            <div class="mt-4 pt-3 border-t border-slate-100 flex items-end justify-between">
                                <div>
                                    <span class="text-xs text-slate-400 block">Saldo Actual:</span>
                                    <span class="font-mono font-extrabold text-lg tabular-nums {{ $esNeg ? 'text-danger-600' : 'text-slate-900' }}">
                                        {{ formato_moneda($caja->saldo_calculado) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1">
                                    @if (! $esActiva)
                                        <form method="POST" action="{{ route('caja.activa') }}">
                                            @csrf
                                            <input type="hidden" name="caja_id" value="{{ $caja->id }}">
                                            <button type="submit" class="text-xs font-semibold px-2.5 py-1.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                                Seleccionar
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('cajas.show', $caja) }}" class="text-xs font-semibold px-2.5 py-1.5 rounded bg-primary-50 hover:bg-primary-100 text-primary-700 transition">
                                        Detalle
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-8 text-center text-slate-400 italic">
                            No tienes cajas asignadas actualmente. Contacta al Administrador General.
                        </div>
                    @endforelse
                </div>
            </x-ui.card>

            <!-- Gráfica Comparativa y Top 5 Egresos en Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Gráfica Mensual de Caja Activa -->
                <div class="lg:col-span-2">
                    <x-ui.card class="h-full flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                                <div>
                                    <h2 class="text-lg font-bold text-slate-900">Ingresos vs Egresos {{ $anioActual }}</h2>
                                    <p class="text-xs text-slate-500">
                                        Flujo mensual de la caja activa: <span class="font-semibold text-slate-700">{{ $cajaActiva?->nombre ?? 'Sin caja' }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="pt-4">
                                <x-charts.barras
                                    id="chart-tesorero-activa"
                                    :labels="$chartData['labels']"
                                    :datasets="$chartData['datasets']"
                                    height="300"
                                />
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                <!-- Top 5 Egresos del Mes -->
                <div>
                    <x-ui.card class="h-full flex flex-col justify-between">
                        <div>
                            <div class="pb-4 border-b border-slate-200">
                                <h2 class="text-lg font-bold text-slate-900">Top 5 Egresos del Mes</h2>
                                <p class="text-xs text-slate-500">{{ now()->translatedFormat('F Y') }} &bull; Por cuenta contable</p>
                            </div>

                            <div class="mt-4 divide-y divide-slate-100">
                                @forelse ($topEgresos as $egreso)
                                    <div class="py-3 flex items-center justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-mono text-xs font-bold text-slate-500">{{ $egreso['codigo'] }}</span>
                                                <span class="text-xs font-semibold text-slate-800 truncate block">{{ $egreso['nombre'] }}</span>
                                            </div>
                                        </div>
                                        <span class="font-mono font-bold text-xs tabular-nums text-rose-600 whitespace-nowrap">
                                            {{ formato_moneda($egreso['total']) }}
                                        </span>
                                    </div>
                                @empty
                                    <div class="py-8 text-center text-slate-400 italic text-xs">
                                        No hay egresos registrados en el mes actual.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                        <div class="pt-4 border-t border-slate-100 mt-4">
                            <x-ui.button href="{{ route('egresos.create', ['caja_id' => $cajaActiva?->id]) }}" variant="danger" size="sm" class="w-full justify-center">
                                + Registrar Nuevo Egreso
                            </x-ui.button>
                        </div>
                    </x-ui.card>
                </div>
            </div>

            <!-- Últimos Movimientos de la Caja Activa -->
            <x-ui.card>
                <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Últimos Movimientos de {{ $cajaActiva?->nombre }}</h2>
                        <p class="text-xs text-slate-500">Historial reciente de ingresos y egresos.</p>
                    </div>
                    <div class="flex gap-2">
                        <x-ui.button href="{{ route('ingresos.create', ['caja_id' => $cajaActiva?->id]) }}" variant="success" size="sm">
                            + Ingreso
                        </x-ui.button>
                        <x-ui.button href="{{ route('egresos.create', ['caja_id' => $cajaActiva?->id]) }}" variant="danger" size="sm">
                            + Egreso
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
                                <th class="px-4 py-3">Detalle</th>
                                <th class="px-4 py-3 text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($ultimosMovimientos as $mov)
                                @php $esIng = $mov->tipo_movimiento === 'ingreso'; @endphp
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ $esIng ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                            {{ $esIng ? '+ Ingreso' : '- Egreso' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap font-mono text-xs text-slate-600">
                                        {{ $mov->fecha?->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-slate-700 text-xs">
                                        <span class="font-mono text-slate-500 font-bold mr-1">{{ $mov->cuenta?->codigo }}</span>
                                        {{ $mov->cuenta?->nombre }}
                                    </td>
                                    <td class="px-4 py-2.5 text-slate-600 text-xs">
                                        {{ $esIng ? ($mov->recibo ?? 'Sin recibo') : Str::limit($mov->descripcion, 40) }}
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-right font-mono font-bold tabular-nums {{ $esIng ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ $esIng ? '+' : '-' }} {{ formato_moneda($mov->monto) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-400 italic">No hay movimientos recientes en esta caja.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

        @endif
    </div>
</x-app-layout>
