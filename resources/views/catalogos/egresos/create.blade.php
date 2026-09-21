<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Nueva Cuenta de Egreso</h1>
                <p class="text-sm text-slate-500">Registra un nuevo concepto de gasto en la nomenclatura contable.</p>
            </div>
            <x-ui.button href="{{ route('catalogos.egresos.index') }}" variant="secondary">
                Volver a la lista
            </x-ui.button>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <x-ui.card>
            <form method="POST" action="{{ route('catalogos.egresos.store') }}" class="space-y-6">
                @csrf

                <x-form.input
                    label="Código Único (3 a 10 caracteres) *"
                    name="codigo"
                    placeholder="Ej. 037 o 201"
                    required
                />

                <x-form.input
                    label="Nombre de la Cuenta *"
                    name="nombre"
                    placeholder="Ej. Reparación y Mantenimiento de Instrumentos"
                    required
                />

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                    <x-ui.button href="{{ route('catalogos.egresos.index') }}" variant="secondary">
                        Cancelar
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Guardar Cuenta de Egreso
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-app-layout>
