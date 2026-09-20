<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Cortes de Caja</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Cierres periódicos de caja, snapshots contables inmutables y control de aprobación (RN-08, RN-09).
                </p>
            </div>
            @can('cortes.solicitar')
                <div>
                    <x-ui.button href="{{ route('cortes.create') }}" variant="primary">
                        + Solicitar Corte
                    </x-ui.button>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filtros de búsqueda -->
        <x-ui.card>
            <form method="GET" action="{{ route('cortes.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div>
                    <label for="caja_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Filtrar por Caja
                    </label>
                    <select name="caja_id" id="caja_id" class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Todas mis cajas accesibles</option>
                        @foreach ($cajas as $caja)
                            <option value="{{ $caja->id }}" @selected(request('caja_id') == $caja->id)>
                                {{ $caja->codigo }} &bull; {{ $caja->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="estado" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Estado del Corte
                    </label>
                    <select name="estado" id="estado" class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Todos los estados</option>
                        @foreach ($estados as $est)
                            <option value="{{ $est->value }}" @selected(request('estado') == $est->value)>
                                {{ $est->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <x-ui.button type="submit" variant="primary" class="w-full justify-center">
                        Filtrar
                    </x-ui.button>
                    @if (request()->hasAny(['caja_id', 'estado']))
                        <x-ui.button href="{{ route('cortes.index') }}" variant="secondary">
                            Limpiar
                        </x-ui.button>
                    @endif
                </div>
            </form>
        </x-ui.card>

        <!-- Tabla de Cortes -->
        <x-ui.card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3">Período Contable</th>
                            <th class="px-4 py-3">Caja</th>
                            <th class="px-4 py-3 text-right">Saldo Inicial</th>
                            <th class="px-4 py-3 text-right">Ingresos</th>
                            <th class="px-4 py-3 text-right">Egresos</th>
                            <th class="px-4 py-3 text-right">Saldo Final</th>
                            <th class="px-4 py-3 text-center">Estado</th>
                            <th class="px-4 py-3">Solicitado Por</th>
                            <th class="px-4 py-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($cortes as $corte)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-slate-800 font-bold">
                                    {{ $corte->periodo_inicio?->format('d/m/Y') }} &rarr; {{ $corte->periodo_fin?->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-800 font-medium">
                                    <span class="font-mono text-slate-500 font-bold mr-1">{{ $corte->caja?->codigo }}</span>
                                    {{ $corte->caja?->nombre }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right font-mono text-xs tabular-nums text-slate-700">
                                    {{ formato_moneda($corte->saldo_inicial) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right font-mono text-xs tabular-nums text-emerald-600 font-semibold">
                                    + {{ formato_moneda($corte->total_ingresos) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right font-mono text-xs tabular-nums text-rose-600 font-semibold">
                                    - {{ formato_moneda($corte->total_egresos) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right font-mono text-xs tabular-nums font-bold text-slate-900">
                                    {{ formato_moneda($corte->saldo_final) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <x-ui.badge :variant="$corte->estado->badgeVariant()">
                                        {{ $corte->estado->label() }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600">
                                    <span class="block font-medium text-slate-800">{{ $corte->solicitante?->name ?? 'Sistema' }}</span>
                                    <span class="text-[11px] text-slate-400 font-mono">{{ $corte->solicitado_at?->format('d/m/Y H:i') }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-xs">
                                    <a href="{{ route('cortes.show', $corte) }}" class="inline-flex items-center px-2.5 py-1 rounded bg-primary-50 text-primary-700 hover:bg-primary-100 font-medium transition">
                                        Ver Detalle
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-12 text-center text-slate-400 italic">
                                    No se encontraron registros de cortes de caja con los criterios seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($cortes->hasPages())
                <div class="mt-4 pt-4 border-t border-slate-200">
                    {{ $cortes->links() }}
                </div>
            @endif
        </x-ui.card>
    </div>
</x-app-layout>
