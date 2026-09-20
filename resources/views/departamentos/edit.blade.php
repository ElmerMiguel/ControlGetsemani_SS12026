<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Editar Departamento</h1>
                <p class="text-sm text-slate-500">Modifica los datos del departamento {{ $departamento->nombre }}.</p>
            </div>
            <x-ui.button href="{{ route('departamentos.index') }}" variant="secondary">
                Volver a la lista
            </x-ui.button>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <x-ui.card>
            <form method="POST" action="{{ route('departamentos.update', $departamento) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <x-form.input
                    label="Nombre del Departamento *"
                    name="nombre"
                    :value="$departamento->nombre"
                    required
                />

                <x-form.select
                    label="Tipo de Entidad *"
                    name="tipo"
                    required
                >
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo->value }}" @selected(old('tipo', $departamento->tipo->value) === $tipo->value)>
                            {{ ucfirst($tipo->value) }}
                        </option>
                    @endforeach
                </x-form.select>

                <div>
                    <label for="descripcion" class="block text-sm font-medium text-slate-700 mb-1">
                        Descripción (Opcional)
                    </label>
                    <textarea
                        name="descripcion"
                        id="descripcion"
                        rows="3"
                        class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm w-full text-sm text-ink"
                    >{{ old('descripcion', $departamento->descripcion) }}</textarea>
                    @error('descripcion')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                    <x-ui.button href="{{ route('departamentos.index') }}" variant="secondary">
                        Cancelar
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Actualizar Departamento
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-app-layout>
