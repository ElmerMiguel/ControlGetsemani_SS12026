<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Registrar Ingreso</h1>
                <p class="text-sm text-slate-500">Captura rápida de diezmos, ofrendas y donaciones.</p>
            </div>
            <x-ui.button href="{{ route('ingresos.index') }}" variant="secondary">
                Volver a la lista
            </x-ui.button>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <x-ui.card>
            <form method="POST" action="{{ route('ingresos.store') }}" class="space-y-6" id="form-ingreso">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Caja de Destino -->
                    <div>
                        <x-form.select
                            label="Caja Contable de Destino *"
                            name="caja_id"
                            required
                        >
                            @foreach ($cajas as $caja)
                                <option value="{{ $caja->id }}" @selected(old('caja_id', $cajaPreseleccionadaId) == $caja->id)>
                                    {{ $caja->codigo }} - {{ $caja->nombre }} ({{ ucfirst($caja->medio->value) }})
                                </option>
                            @endforeach
                        </x-form.select>
                    </div>

                    <!-- 1. Fecha (Foco inicial por defecto, hoy) -->
                    <div>
                        <label for="fecha" class="block text-sm font-medium text-slate-700 mb-1">
                            Fecha del Movimiento *
                        </label>
                        <input
                            type="date"
                            name="fecha"
                            id="fecha"
                            value="{{ old('fecha', $fechaPreseleccionada) }}"
                            max="{{ date('Y-m-d') }}"
                            required
                            tabindex="1"
                            @if(!session('foco_cuenta')) autofocus @endif
                            class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm w-full text-sm text-ink"
                        />
                        @error('fecha')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 2. Cuenta de Ingreso (x-form.cuenta-selector con Alpine) -->
                    <div class="md:col-span-2">
                        <x-form.cuenta-selector
                            label="Cuenta de Ingreso * (Escribe código numérico o nombre)"
                            name="cuenta_ingreso_id"
                            :cuentas="$cuentas"
                            :value="old('cuenta_ingreso_id')"
                            required
                        />
                    </div>

                    <!-- 3. Monto (Quetzales) -->
                    <div>
                        <x-form.money
                            label="Monto en Quetzales *"
                            name="monto"
                            :value="old('monto')"
                            tabindex="3"
                            required
                        />
                    </div>

                    <!-- 4. Recibo / Comprobante -->
                    <div>
                        <x-form.input
                            label="No. de Recibo / Comprobante (Opcional)"
                            name="recibo"
                            placeholder="Ej. REC-00125"
                            tabindex="4"
                        />
                    </div>

                    <!-- Aportante (Miembro o donante) -->
                    <div class="md:col-span-2">
                        <x-form.select
                            label="Aportante / Donante (Opcional)"
                            name="aportante_id"
                            tabindex="5"
                        >
                            <option value="">-- Aportante anónimo / no especificado --</option>
                            @foreach ($aportantes as $aportante)
                                <option value="{{ $aportante->id }}" @selected(old('aportante_id') == $aportante->id)>
                                    {{ $aportante->nombre_completo }} {{ $aportante->cui_dpi ? '(' . $aportante->cui_enmascarado . ')' : '' }}
                                </option>
                            @endforeach
                        </x-form.select>
                    </div>

                    <!-- Observaciones -->
                    <div class="md:col-span-2">
                        <label for="observaciones" class="block text-sm font-medium text-slate-700 mb-1">
                            Observaciones (Opcional)
                        </label>
                        <textarea
                            name="observaciones"
                            id="observaciones"
                            rows="2"
                            tabindex="6"
                            placeholder="Detalles adicionales sobre este ingreso..."
                            class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm w-full text-sm text-ink"
                        >{{ old('observaciones') }}</textarea>
                        @error('observaciones')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Botones de Acción: Guardar y Guardar y Nuevo -->
                <div class="flex flex-wrap items-center justify-end gap-3 pt-6 border-t border-slate-200">
                    <x-ui.button href="{{ route('ingresos.index') }}" variant="secondary">
                        Cancelar
                    </x-ui.button>
                    
                    <button
                        type="submit"
                        name="guardar_y_nuevo"
                        value="1"
                        tabindex="7"
                        class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 active:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition shadow-sm"
                    >
                        <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Guardar y nuevo
                    </button>

                    <x-ui.button type="submit" variant="primary" tabindex="8">
                        Guardar Ingreso
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>

    @if (session('foco_cuenta'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => {
                    const cuentaInput = document.querySelector('input[placeholder*="Escribe código"]');
                    if (cuentaInput) {
                        cuentaInput.focus();
                    }
                }, 100);
            });
        </script>
    @endif
</x-app-layout>
