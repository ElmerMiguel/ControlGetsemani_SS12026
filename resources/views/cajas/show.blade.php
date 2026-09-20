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
                @can('cajas.gestionar')
                    <x-ui.button href="{{ route('cajas.edit', $caja) }}" variant="primary">
                        Editar Caja
                    </x-ui.button>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Tarjetas de Información Rápida (Esqueleto P8) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-ui.card>
                <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Saldo de Apertura</div>
                <div class="mt-2 text-2xl font-bold text-slate-900 font-mono tabular-nums">
                    Q {{ number_format($caja->saldo_apertura, 2) }}
                </div>
                <div class="mt-1 text-xs text-slate-400">
                    Aperturada el {{ $caja->fecha_apertura?->format('d/m/Y') }}
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Tesoreros Encargados</div>
                <div class="mt-2 text-sm text-slate-800">
                    @forelse ($caja->tesoreros as $tesorero)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-50 text-primary-700 border border-primary-200 me-1 mb-1">
                            {{ $tesorero->name }}
                        </span>
                    @empty
                        <span class="text-xs text-slate-400 italic">Sin tesoreros asignados</span>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Estado y Operación</div>
                <div class="mt-2 flex items-center gap-2">
                    <span class="text-sm font-semibold text-slate-800">
                        {{ $caja->activa ? 'Operativa para movimientos' : 'Bloqueada para nuevos movimientos' }}
                    </span>
                </div>
                <div class="mt-1 text-xs text-slate-400">
                    {{ ucfirst($caja->medio->value) }} &bull; Departamento: {{ $caja->departamento->nombre }}
                </div>
            </x-ui.card>
        </div>

        <!-- Placeholder para Movimientos Recientes y Cortes (se implementa en P8) -->
        <x-ui.card>
            <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Historial de Operaciones</h2>
                    <p class="text-xs text-slate-500">Resumen y últimos movimientos contables de la caja.</p>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                    Módulo de Movimientos en desarrollo (P7 / P8)
                </span>
            </div>

            <div class="py-12 text-center text-slate-400 text-sm">
                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                Los registros de ingresos, egresos y cortes periódicos de esta caja se mostrarán en esta sección al completar los módulos P7 y P8.
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
