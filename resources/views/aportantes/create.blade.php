<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Nuevo Aportante</h1>
                <p class="text-sm text-slate-500">Registra a un miembro o donante para el control de aportaciones.</p>
            </div>
            <x-ui.button href="{{ route('aportantes.index') }}" variant="secondary">
                Volver a la lista
            </x-ui.button>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <x-ui.card>
            <form method="POST" action="{{ route('aportantes.store') }}" class="space-y-6">
                @csrf

                <x-form.input
                    label="Nombre Completo *"
                    name="nombre_completo"
                    placeholder="Ej. Juan Carlos López Morales"
                    required
                />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-form.input
                            label="CUI / DPI (Opcional - 13 dígitos)"
                            name="cui_dpi"
                            placeholder="Ej. 2541890120101"
                            maxlength="13"
                        />
                        <p class="mt-1 text-xs text-slate-500">Solo números, sin guiones ni espacios.</p>
                    </div>

                    <div>
                        <x-form.input
                            label="Teléfono de Contacto (Opcional)"
                            name="telefono"
                            placeholder="Ej. 5555-1234"
                            maxlength="20"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                    <x-ui.button href="{{ route('aportantes.index') }}" variant="secondary">
                        Cancelar
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Guardar Aportante
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-app-layout>
