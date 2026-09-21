<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Editar Egreso #{{ $egreso->id }}</h1>
                <p class="text-sm text-slate-500">Modifica los datos del movimiento de egreso.</p>
            </div>
            <x-ui.button href="{{ route('egresos.index') }}" variant="secondary">
                Volver a la lista
            </x-ui.button>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <x-ui.card>
            <form method="POST" action="{{ route('egresos.update', $egreso) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-form.select
                            label="Caja de Origen del Fondo *"
                            name="caja_id"
                            required
                        >
                            @foreach ($cajas as $caja)
                                <option value="{{ $caja->id }}" @selected(old('caja_id', $egreso->caja_id) == $caja->id)>
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
                            value="{{ old('fecha', $egreso->fecha?->format('Y-m-d')) }}"
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
                            label="Cuenta de Egreso *"
                            name="cuenta_egreso_id"
                            :cuentas="$cuentas"
                            :value="old('cuenta_egreso_id', $egreso->cuenta_egreso_id)"
                            required
                        />
                    </div>

                    <div>
                        <x-form.money
                            label="Monto del Desembolso *"
                            name="monto"
                            :value="old('monto', $egreso->monto)"
                            required
                        />
                    </div>

                    <div>
                        <x-form.input
                            label="No. de Factura / Cheque / Recibo (Opcional)"
                            name="referencia"
                            :value="old('referencia', $egreso->referencia)"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label for="descripcion" class="block text-sm font-medium text-slate-700 mb-1">
                            Descripción del Gasto / Concepto *
                        </label>
                        <textarea
                            name="descripcion"
                            id="descripcion"
                            rows="2"
                            required
                            class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm w-full text-sm text-ink"
                        >{{ old('descripcion', $egreso->descripcion) }}</textarea>
                        @error('descripcion')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200">
                    <x-ui.button href="{{ route('egresos.index') }}" variant="secondary">
                        Cancelar
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Actualizar Egreso
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-app-layout>
