<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Registro de Auditoría #{{ $bitacora->id }}</h1>
                <p class="text-sm text-slate-500">Consulta histórica inmutable de operación (RN-16).</p>
            </div>
            <x-ui.button href="{{ route('bitacora.index') }}" variant="secondary">
                Volver a la Bitácora
            </x-ui.button>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Metadatos de la Operación -->
        <x-ui.card>
            <h2 class="text-base font-semibold text-slate-900 border-b border-slate-100 pb-3 mb-4 flex items-center justify-between">
                <span>Información del Evento</span>
                <span class="text-xs font-mono font-normal text-slate-400">ID: {{ $bitacora->id }}</span>
            </h2>

            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Fecha y Hora</dt>
                    <dd class="mt-1 font-mono text-slate-800">{{ $bitacora->fecha_hora->format('d/m/Y H:i:s') }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Usuario Responsable</dt>
                    <dd class="mt-1 text-slate-800">
                        @if ($bitacora->usuario)
                            <span class="font-medium">{{ $bitacora->usuario->name }}</span>
                            <span class="text-xs text-slate-500">({{ $bitacora->usuario->email }})</span>
                        @else
                            <span class="italic text-slate-500">Sistema (Automático)</span>
                        @endif
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Acción</dt>
                    <dd class="mt-1">
                        <x-ui.badge variant="purple">{{ $bitacora->accion }}</x-ui.badge>
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Registro Afectado</dt>
                    <dd class="mt-1 font-mono text-slate-800">
                        @if ($bitacora->tabla_afectada)
                            {{ $bitacora->tabla_afectada }} #{{ $bitacora->registro_id }}
                        @else
                            <span class="text-slate-400 italic">No aplica</span>
                        @endif
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Dirección IP</dt>
                    <dd class="mt-1 font-mono text-slate-800">{{ $bitacora->ip ?? 'Desconocida' }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Navegador / Cliente</dt>
                    <dd class="mt-1 text-xs text-slate-600 truncate" title="{{ $bitacora->user_agent }}">
                        {{ $bitacora->user_agent ?? 'Consola o Sistema' }}
                    </dd>
                </div>

                <div class="md:col-span-2 pt-3 border-t border-slate-100">
                    <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Descripción Completa</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900 bg-slate-50 p-3 rounded-lg border border-slate-200">
                        {{ $bitacora->descripcion }}
                    </dd>
                </div>
            </dl>
        </x-ui.card>

        <!-- Comparación de Datos Antes / Después -->
        @php
            $campos = array_unique(array_merge(
                array_keys($bitacora->datos_antes ?? []),
                array_keys($bitacora->datos_despues ?? [])
            ));
            sort($campos);
        @endphp

        <x-ui.card :padding="false">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-base font-semibold text-slate-900">Detalle de Cambios de Atributos</h2>
                <p class="text-xs text-slate-500">Comparativa campo a campo entre el estado previo y posterior (contraseñas excluidas por seguridad).</p>
            </div>

            @if (! empty($campos))
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3 font-semibold w-1/4">Campo</th>
                                <th class="px-6 py-3 font-semibold w-3/8 text-red-700 bg-red-50/50">Valor Anterior (Antes)</th>
                                <th class="px-6 py-3 font-semibold w-3/8 text-emerald-700 bg-emerald-50/50">Valor Nuevo (Después)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-mono text-xs">
                            @foreach ($campos as $campo)
                                @php
                                    $antesVal = $bitacora->datos_antes[$campo] ?? null;
                                    $despuesVal = $bitacora->datos_despues[$campo] ?? null;

                                    $formatoVal = function ($v) {
                                        if (is_null($v)) return '<span class="text-slate-400 italic">null</span>';
                                        if (is_bool($v)) return $v ? '<span class="text-emerald-600 font-bold">true</span>' : '<span class="text-red-600 font-bold">false</span>';
                                        if (is_array($v)) return htmlspecialchars(json_encode($v, JSON_UNESCAPED_UNICODE));
                                        return htmlspecialchars((string) $v);
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-6 py-3 font-bold text-slate-800 bg-slate-50/30">
                                        {{ $campo }}
                                    </td>
                                    <td class="px-6 py-3 text-red-800 bg-red-50/20">
                                        {!! $formatoVal($antesVal) !!}
                                    </td>
                                    <td class="px-6 py-3 text-emerald-800 bg-emerald-50/20">
                                        {!! $formatoVal($despuesVal) !!}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-8 text-center text-slate-400 text-sm">
                    Este evento de auditoría no registró cambios de campos directos en base de datos.
                </div>
            @endif
        </x-ui.card>
    </div>
</x-app-layout>
