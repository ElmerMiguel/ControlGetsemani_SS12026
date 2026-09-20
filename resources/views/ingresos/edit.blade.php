<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Editar Ingreso #{{ $ingreso->id }}</h1>
                <p class="text-sm text-slate-500">Modifica los datos del movimiento de ingreso.</p>
            </div>
            <x-ui.button href="{{ route('ingresos.index') }}" variant="secondary">
                Volver a la lista
            </x-ui.button>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <x-ui.card>
            <form method="POST" action="{{ route('ingresos.update', $ingreso) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-form.select
                            label="Caja Contable de Destino *"
                            name="caja_id"
                            required
                        >
                            @foreach ($cajas as $caja)
                                <option value="{{ $caja->id }}" @selected(old('caja_id', $ingreso->caja_id) == $caja->id)>
                                    {{ $caja->codigo }} - {{ $caja->nombre }}
                                </option>
                            @endforeach
                        </x-form.select>
                    </div>

                    <div>
                        <label for="fecha" class="block text-sm font-medium text-slate-700 mb-1">
                            Fecha del Movimiento *
                        </label>
                        <input
                            type="date"
                            name="fecha"
                            id="fecha"
                            value="{{ old('fecha', $ingreso->fecha?->format('Y-m-d')) }}"
                            max="{{ date('Y-m-d') }}"
                            required
                            class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm w-full text-sm text-ink"
                        />
                        @error('fecha')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <x-form.cuenta-selector
                            label="Cuenta de Ingreso *"
                            name="cuenta_ingreso_id"
                            :cuentas="$cuentas"
                            :value="old('cuenta_ingreso_id', $ingreso->cuenta_ingreso_id)"
                            required
                        />
                    </div>

                    <div>
                        <x-form.money
                            label="Monto en Quetzales *"
                            name="monto"
                            :value="old('monto', $ingreso->monto)"
                            required
                        />
                    </div>

                    <div>
                        <x-form.input
                            label="No. de Recibo / Comprobante (Opcional)"
                            name="recibo"
                            :value="old('recibo', $ingreso->recibo)"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <x-form.select
                            label="Aportante / Donante (Opcional)"
                            name="aportante_id"
                        >
                            <option value="">-- Aportante anónimo / no especificado --</option>
                            @foreach ($aportantes as $aportante)
                                <option value="{{ $aportante->id }}" @selected(old('aportante_id', $ingreso->aportante_id) == $aportante->id)>
                                    {{ $aportante->nombre_completo }} {{ $aportante->cui_dpi ? '(' . $aportante->cui_enmascarado . ')' : '' }}
                                </option>
                            @endforeach
                        </x-form.select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="observaciones" class="block text-sm font-medium text-slate-700 mb-1">
                            Observaciones (Opcional)
                        </label>
                        <textarea
                            name="observaciones"
                            id="observaciones"
                            rows="2"
                            class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm w-full text-sm text-ink"
                        >{{ old('observaciones', $ingreso->observaciones) }}</textarea>
                        @error('observaciones')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200">
                    <x-ui.button href="{{ route('ingresos.index') }}" variant="secondary">
                        Cancelar
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Actualizar Ingreso
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-app-layout>
