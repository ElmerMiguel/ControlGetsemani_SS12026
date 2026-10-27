<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Registrar Bien en Inventario</h1>
                <p class="text-sm text-slate-500">Ingreso de un nuevo activo institucional por departamento.</p>
            </div>
            <x-ui.button href="{{ route('inventario.index') }}" variant="secondary">
                Volver al listado
            </x-ui.button>
        </div>
    </x-slot>

    <div class="max-w-3xl">
        <x-ui.card>
            <form method="POST" action="{{ route('inventario.store') }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="departamento_id" class="block text-sm font-medium text-slate-700 mb-1">
                            Departamento <span class="text-rose-500">*</span>
                        </label>
                        <select
                            name="departamento_id"
                            id="departamento_id"
                            required
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-primary-500 focus:ring-primary-500"
                        >
                            <option value="">Seleccione un departamento</option>
                            @foreach ($departamentos as $dep)
                                <option value="{{ $dep->id }}" @selected(old('departamento_id') == $dep->id)>
                                    {{ $dep->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('departamento_id')" class="mt-1" />
                    </div>

                    <div>
                        <label for="codigo" class="block text-sm font-medium text-slate-700 mb-1">
                            Código Institucional <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="codigo"
                            id="codigo"
                            required
                            placeholder="Ej. ACT-001"
                            value="{{ old('codigo') }}"
                            class="w-full rounded-lg border-slate-300 text-sm uppercase focus:border-primary-500 focus:ring-primary-500"
                        />
                        <x-input-error :messages="$errors->get('codigo')" class="mt-1" />
                    </div>
                </div>

                <div>
                    <label for="nombre" class="block text-sm font-medium text-slate-700 mb-1">
                        Nombre del Bien <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="nombre"
                        id="nombre"
                        required
                        placeholder="Ej. Consola de Sonido Yamaha 16 Canales"
                        value="{{ old('nombre') }}"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-primary-500 focus:ring-primary-500"
                    />
                    <x-input-error :messages="$errors->get('nombre')" class="mt-1" />
                </div>

                <div>
                    <label for="descripcion" class="block text-sm font-medium text-slate-700 mb-1">
                        Descripción o Especificaciones
                    </label>
                    <textarea
                        name="descripcion"
                        id="descripcion"
                        rows="3"
                        placeholder="Detalles, número de serie o características físicas..."
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-primary-500 focus:ring-primary-500"
                    >{{ old('descripcion') }}</textarea>
                    <x-input-error :messages="$errors->get('descripcion')" class="mt-1" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div>
                        <label for="estado" class="block text-sm font-medium text-slate-700 mb-1">
                            Estado Inicial <span class="text-rose-500">*</span>
                        </label>
                        <select
                            name="estado"
                            id="estado"
                            required
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-primary-500 focus:ring-primary-500"
                        >
                            @foreach ($estados as $est)
                                <option value="{{ $est->value }}" @selected(old('estado', 'correcto') === $est->value)>
                                    {{ $est->label() }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('estado')" class="mt-1" />
                    </div>

                    <div>
                        <label for="valor_estimado" class="block text-sm font-medium text-slate-700 mb-1">
                            Valor Estimado (Q)
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="valor_estimado"
                            id="valor_estimado"
                            placeholder="0.00"
                            value="{{ old('valor_estimado') }}"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-primary-500 focus:ring-primary-500"
                        />
                        <x-input-error :messages="$errors->get('valor_estimado')" class="mt-1" />
                    </div>

                    <div>
                        <label for="fecha_registro" class="block text-sm font-medium text-slate-700 mb-1">
                            Fecha de Registro <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="date"
                            name="fecha_registro"
                            id="fecha_registro"
                            required
                            max="{{ date('Y-m-d') }}"
                            value="{{ old('fecha_registro', date('Y-m-d')) }}"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-primary-500 focus:ring-primary-500"
                        />
                        <x-input-error :messages="$errors->get('fecha_registro')" class="mt-1" />
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                    <x-ui.button href="{{ route('inventario.index') }}" variant="secondary">
                        Cancelar
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Guardar Bien
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-app-layout>
