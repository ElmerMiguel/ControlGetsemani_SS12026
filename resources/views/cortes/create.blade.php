<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Solicitar Corte de Caja</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Cierre formal de período para revisión de la Administración General (RN-08).
                </p>
            </div>
            <x-ui.button href="{{ route('cortes.index') }}" variant="secondary">
                Volver a Cortes
            </x-ui.button>
        </div>
    </x-slot>

    <div
        x-data="{
            cajaId: '{{ old('caja_id', $cajaSeleccionada->id) }}',
            periodoFin: '{{ old('periodo_fin', now()->format('Y-m-d')) }}',
            cargando: false,
            error: null,
            preview: null,
            formateado: null,

            async cargarPreview() {
                if (!this.cajaId || !this.periodoFin) return;
                this.cargando = true;
                this.error = null;

                try {
                    const response = await fetch(`{{ route('cortes.preview') }}?caja_id=${this.cajaId}&periodo_fin=${this.periodoFin}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const json = await response.json();
                    if (!response.ok) {
                        this.error = json.message || Object.values(json.errors || {})[0]?.[0] || 'Error al calcular la vista previa.';
                        this.preview = null;
                        this.formateado = null;
                    } else {
                        this.preview = json.data;
                        this.formateado = json.formateado;
                    }
                } catch (e) {
                    this.error = 'No se pudo conectar con el servidor para calcular el snapshot.';
                } finally {
                    this.cargando = false;
                }
            }
        }"
        x-init="cargarPreview()"
        class="max-w-4xl mx-auto space-y-6"
    >
        <x-ui.card>
            <form method="POST" action="{{ route('cortes.store') }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Selección de Caja -->
                    <div>
                        <label for="caja_id" class="block text-sm font-semibold text-slate-700 mb-1">
                            Caja a Cortar *
                        </label>
                        <select
                            name="caja_id"
                            id="caja_id"
                            x-model="cajaId"
                            @change="cargarPreview()"
                            class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500 @error('caja_id') border-rose-500 @enderror"
                            required
                        >
                            @foreach ($cajas as $caja)
                                <option value="{{ $caja->id }}">
                                    {{ $caja->codigo }} &bull; {{ $caja->nombre }} ({{ $caja->departamento->nombre }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-500 mt-1">Solo cajas activas asignadas a tu usuario.</p>
                        @error('caja_id')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Fecha de Fin de Período -->
                    <div>
                        <label for="periodo_fin" class="block text-sm font-semibold text-slate-700 mb-1">
                            Fecha Fin del Período *
                        </label>
                        <input
                            type="date"
                            name="periodo_fin"
                            id="periodo_fin"
                            max="{{ now()->format('Y-m-d') }}"
                            x-model="periodoFin"
                            @change="cargarPreview()"
                            class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500 @error('periodo_fin') border-rose-500 @enderror"
                            required
                        />
                        <p class="text-xs text-slate-500 mt-1">El período de inicio se calcula automáticamente por continuidad contable (RN-08).</p>
                        @error('periodo_fin')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Observaciones Opcionales -->
                <div>
                    <label for="observaciones" class="block text-sm font-semibold text-slate-700 mb-1">
                        Observaciones para el Administrador (Opcional)
                    </label>
                    <textarea
                        name="observaciones"
                        id="observaciones"
                        rows="2"
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                        placeholder="Comentarios adicionales sobre el corte o aclaraciones contables..."
                    >{{ old('observaciones') }}</textarea>
                </div>

                <!-- VISTA PREVIA DEL SNAPSHOT CONTABLE EN VIVO (RN-03, RN-08) -->
                <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                                Vista Previa del Snapshot Contable
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Cálculo en tiempo real de ingresos y egresos vigentes en el período seleccionado.
                            </p>
                        </div>
                        <template x-if="cargando">
                            <span class="inline-flex items-center text-xs text-primary-600 font-semibold gap-1">
                                <svg class="animate-spin h-3.5 w-3.5" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Calculando...
                            </span>
                        </template>
                    </div>

                    <template x-if="error">
                        <div class="p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs">
                            <span class="font-bold">Aviso:</span> <span x-text="error"></span>
                        </div>
                    </template>

                    <template x-if="formateado && !error">
                        <div class="space-y-4">
                            <div class="text-xs text-slate-600 font-medium">
                                Período a cerrar:
                                <span class="font-mono font-bold text-slate-900" x-text="`${formateado.periodo_inicio} al ${formateado.periodo_fin}`"></span>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div class="bg-white p-3 rounded-lg border border-slate-200">
                                    <span class="text-[11px] font-semibold text-slate-500 uppercase block">Saldo Inicial</span>
                                    <span class="mt-1 block font-mono text-base font-bold text-slate-900 tabular-nums" x-text="formateado.saldo_inicial"></span>
                                </div>
                                <div class="bg-white p-3 rounded-lg border border-slate-200">
                                    <span class="text-[11px] font-semibold text-slate-500 uppercase block">Ingresos (+)</span>
                                    <span class="mt-1 block font-mono text-base font-bold text-emerald-600 tabular-nums" x-text="formateado.total_ingresos"></span>
                                </div>
                                <div class="bg-white p-3 rounded-lg border border-slate-200">
                                    <span class="text-[11px] font-semibold text-slate-500 uppercase block">Egresos (-)</span>
                                    <span class="mt-1 block font-mono text-base font-bold text-rose-600 tabular-nums" x-text="formateado.total_egresos"></span>
                                </div>
                                <div class="bg-white p-3 rounded-lg border border-primary-200 bg-primary-50/20">
                                    <span class="text-[11px] font-semibold text-primary-700 uppercase block">Saldo Final</span>
                                    <span class="mt-1 block font-mono text-base font-bold text-primary-700 tabular-nums" x-text="formateado.saldo_final"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Advertencia de Bloqueo de Período (RN-05) -->
                <div class="p-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-start gap-2.5">
                    <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <span class="font-bold">Regla de Bloqueo Contable (RN-05):</span>
                        Al solicitar este corte, todos los movimientos correspondientes a las fechas comprendidas quedarán bloqueados para edición y anulación mientras el corte esté pendiente o aprobado.
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                    <x-ui.button href="{{ route('cortes.index') }}" variant="secondary">
                        Cancelar
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary" :disabled="error !== null">
                        Confirmar y Solicitar Corte
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-app-layout>
