<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-slate-900">
                        Corte de Caja: {{ $corte->caja?->nombre }}
                    </h1>
                    <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                        {{ $corte->caja?->codigo }}
                    </span>
                    <x-ui.badge :variant="$corte->estado->badgeVariant()">
                        {{ $corte->estado->label() }}
                    </x-ui.badge>
                </div>
                <p class="text-sm text-slate-500 mt-1">
                    Período: <span class="font-mono font-semibold text-slate-800">{{ $corte->periodo_inicio?->format('d/m/Y') }} al {{ $corte->periodo_fin?->format('d/m/Y') }}</span>
                    &bull; {{ $corte->caja?->departamento?->nombre }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <x-ui.button href="{{ route('cortes.index') }}" variant="secondary">
                    Volver a Cortes
                </x-ui.button>
            </div>
        </div>
    </x-slot>

    <div x-data="{ modalRechazar: false, modalReabrir: false }" class="space-y-6">
        <!-- Barra de Acciones de Administración (RN-09) -->
        @if ($corte->estado->value === 'pendiente' && auth()->user()->can('cortes.aprobar'))
            <div class="p-4 rounded-xl border border-sky-200 bg-sky-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-sky-100 text-sky-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-sky-950">Acciones de Revisión de Corte</h3>
                        <p class="text-xs text-sky-700">Como Administrador General, verifique el snapshot y los movimientos antes de dictaminar.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Formulario de Aprobación -->
                    <form method="POST" action="{{ route('cortes.aprobar', $corte) }}" onsubmit="return confirm('¿Confirma que desea APROBAR este corte de caja? Se verificará el snapshot contable.');">
                        @csrf
                        <x-ui.button type="submit" variant="success">
                            Aprobar Corte
                        </x-ui.button>
                    </form>

                    <!-- Botón para Abrir Modal de Rechazo -->
                    <x-ui.button type="button" @click="modalRechazar = true" variant="danger">
                        Rechazar...
                    </x-ui.button>
                </div>
            </div>
        @endif

        @if ($esUltimoAprobado && auth()->user()->can('cortes.reabrir'))
            <div class="p-4 rounded-xl border border-rose-200 bg-rose-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-rose-100 text-rose-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-rose-950">Reapertura Excepcional de Corte (RN-09)</h3>
                        <p class="text-xs text-rose-700">Este es el último corte aprobado de la caja. Si existieron errores contables, puede reabrirlo.</p>
                    </div>
                </div>

                <x-ui.button type="button" @click="modalReabrir = true" variant="danger">
                    Reabrir Corte...
                </x-ui.button>
            </div>
        @endif

        <!-- Snapshot Contable Guardado (RN-03, RN-08) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-ui.card>
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Saldo Inicial</span>
                <span class="mt-2 block font-mono text-2xl font-extrabold text-slate-800 tabular-nums">
                    {{ formato_moneda($corte->saldo_inicial) }}
                </span>
                <span class="text-xs text-slate-400 mt-1 block">Al cierre del día anterior</span>
            </x-ui.card>

            <x-ui.card>
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Ingresos del Período</span>
                <span class="mt-2 block font-mono text-2xl font-extrabold text-emerald-600 tabular-nums">
                    + {{ formato_moneda($corte->total_ingresos) }}
                </span>
                <span class="text-xs text-slate-400 mt-1 block">Entradas registradas</span>
            </x-ui.card>

            <x-ui.card>
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Egresos del Período</span>
                <span class="mt-2 block font-mono text-2xl font-extrabold text-rose-600 tabular-nums">
                    - {{ formato_moneda($corte->total_egresos) }}
                </span>
                <span class="text-xs text-slate-400 mt-1 block">Salidas ejecutadas</span>
            </x-ui.card>

            <x-ui.card class="bg-primary-50/20 border-primary-200">
                <span class="text-xs font-semibold text-primary-700 uppercase tracking-wider block">Saldo Final</span>
                <span class="mt-2 block font-mono text-2xl font-extrabold text-primary-700 tabular-nums">
                    {{ formato_moneda($corte->saldo_final) }}
                </span>
                <span class="text-xs text-slate-400 mt-1 block">Snapshot inmutable</span>
            </x-ui.card>
        </div>

        <!-- Línea de Tiempo y Auditoría del Corte -->
        <x-ui.card>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                <div class="space-y-2">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">1. Solicitud</span>
                    <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                        <p class="font-semibold text-slate-800">{{ $corte->solicitante?->name ?? 'Usuario del Sistema' }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Solicitado el {{ $corte->solicitado_at?->format('d/m/Y \a \l\a\s H:i') ?? 'Sin fecha' }}
                        </p>
                    </div>
                </div>

                <div class="space-y-2">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">2. Dictamen / Revisión</span>
                    <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                        @if ($corte->revisor)
                            <p class="font-semibold text-slate-800">{{ $corte->revisor->name }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Revisado el {{ $corte->revisado_at?->format('d/m/Y \a \l\a\s H:i') }}
                            </p>
                        @else
                            <p class="text-xs text-slate-400 italic">Pendiente de revisión por Administración General</p>
                        @endif
                    </div>
                </div>
            </div>

            @if ($corte->observaciones)
                <div class="mt-4 pt-4 border-t border-slate-200">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">
                        Observaciones y Dictámenes:
                    </span>
                    <div class="p-3 rounded-lg bg-slate-100 text-slate-700 text-xs font-mono whitespace-pre-line">
                        {{ $corte->observaciones }}
                    </div>
                </div>
            @endif
        </x-ui.card>

        <!-- Tabla de Movimientos del Período -->
        <x-ui.card>
            <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Movimientos Incluidos en el Período</h2>
                    <p class="text-xs text-slate-500">
                        {{ $movimientos->count() }} operaciones registradas entre el {{ $corte->periodo_inicio?->format('d/m/Y') }} y el {{ $corte->periodo_fin?->format('d/m/Y') }}.
                    </p>
                </div>
                <div class="flex items-center gap-1.5 text-xs text-slate-500 font-mono">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Ingresos
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-rose-500 ml-2"></span> Egresos
                </div>
            </div>

            <div class="overflow-x-auto mt-4">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Cuenta Contable</th>
                            <th class="px-4 py-3">Detalle / Referencia</th>
                            <th class="px-4 py-3 text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($movimientos as $mov)
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
                                <td class="px-4 py-2.5 whitespace-nowrap text-slate-800 text-xs">
                                    <span class="font-mono text-slate-500 font-bold mr-1">{{ $mov->cuenta?->codigo }}</span>
                                    {{ $mov->cuenta?->nombre }}
                                </td>
                                <td class="px-4 py-2.5 text-slate-600 text-xs">
                                    @if ($esIng)
                                        <span>{{ $mov->recibo ?? 'Sin recibo' }}</span>
                                        @if ($mov->aportante)
                                            <span class="text-slate-400 block text-[11px]">Aportante: {{ $mov->aportante->nombre_completo }}</span>
                                        @endif
                                    @else
                                        <span>{{ Str::limit($mov->descripcion, 50) }}</span>
                                        @if ($mov->referencia)
                                            <span class="text-slate-400 block text-[11px]">Ref: {{ $mov->referencia }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap text-right font-mono font-bold tabular-nums {{ $esIng ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ $esIng ? '+' : '-' }} {{ formato_moneda($mov->monto) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-400 italic">
                                    No se registraron movimientos contables en este período.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <!-- Modal Rechazar Corte -->
        <div
            x-show="modalRechazar"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
        >
            <div @click.away="modalRechazar = false" class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-lg font-bold text-slate-900">Rechazar Corte de Caja</h3>
                <p class="text-xs text-slate-500">
                    Al rechazar el corte, el período quedará desbloqueado para que el tesorero realice los ajustes necesarios.
                </p>

                <form method="POST" action="{{ route('cortes.rechazar', $corte) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="obs_rechazo" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Observación de Rechazo * (mínimo 10 caracteres)
                        </label>
                        <textarea
                            name="observaciones"
                            id="obs_rechazo"
                            rows="3"
                            required
                            minlength="10"
                            class="w-full text-sm rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500"
                            placeholder="Explique detalladamente qué inconsistencias o errores deben corregirse..."
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <x-ui.button type="button" @click="modalRechazar = false" variant="secondary">
                            Cancelar
                        </x-ui.button>
                        <x-ui.button type="submit" variant="danger">
                            Confirmar Rechazo
                        </x-ui.button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Reabrir Corte -->
        <div
            x-show="modalReabrir"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
        >
            <div @click.away="modalReabrir = false" class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
                <h3 class="text-lg font-bold text-slate-900">Reabrir Corte de Caja</h3>
                <p class="text-xs text-slate-500">
                    Se reabrirá el último corte aprobado. El período contable volverá a quedar desbloqueado para permitir modificaciones.
                </p>

                <form method="POST" action="{{ route('cortes.reabrir', $corte) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="motivo_reapertura" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Motivo de Reapertura * (mínimo 10 caracteres)
                        </label>
                        <textarea
                            name="observaciones"
                            id="motivo_reapertura"
                            rows="3"
                            required
                            minlength="10"
                            class="w-full text-sm rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500"
                            placeholder="Describa la justificación institucional para la reapertura del período..."
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <x-ui.button type="button" @click="modalReabrir = false" variant="secondary">
                            Cancelar
                        </x-ui.button>
                        <x-ui.button type="submit" variant="danger">
                            Confirmar Reapertura
                        </x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
