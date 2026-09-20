<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Editar Cuenta de Ingreso: {{ $catalogoIngreso->nombre }}</h1>
                <p class="text-sm text-slate-500">Actualiza la denominación o código de la cuenta.</p>
            </div>
            <x-ui.button href="{{ route('catalogos.ingresos.index') }}" variant="secondary">
                Volver a la lista
            </x-ui.button>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <x-ui.card>
            <form method="POST" action="{{ route('catalogos.ingresos.update', $catalogoIngreso) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <x-form.input
                    label="Código Único (3 a 10 caracteres) *"
                    name="codigo"
                    :value="$catalogoIngreso->codigo"
                    required
                />

                <x-form.input
                    label="Nombre de la Cuenta *"
                    name="nombre"
                    :value="$catalogoIngreso->nombre"
                    required
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                    <x-ui.button href="{{ route('catalogos.ingresos.index') }}" variant="secondary">
                        Cancelar
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Actualizar Cuenta
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-app-layout>
