<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-800 leading-tight">
            Panel de Control
        </h2>
    </x-slot>

    <div class="space-y-6">
        <!-- Saludo y Rol -->
        <x-ui.card>
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Hola, {{ $user->name }}</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Rol actual: <span class="font-semibold text-primary-600">{{ $rol }}</span>
                    </p>
                </div>
                <div>
                    <x-ui.badge variant="info">
                        Sesión Activa
                    </x-ui.badge>
                </div>
            </div>
        </x-ui.card>

        <!-- Tarjetas de Estadísticas Placeholder -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.stat
                label="Saldo de Caja"
                value="Q 0.00"
                subtext="Caja activa seleccionada"
                color="primary"
            />
            <x-ui.stat
                label="Ingresos del Mes"
                value="Q 0.00"
                subtext="Total registrado"
                color="success"
            />
            <x-ui.stat
                label="Egresos del Mes"
                value="Q 0.00"
                subtext="Total ejecutado"
                color="danger"
            />
            <x-ui.stat
                label="Cortes Pendientes"
                value="0"
                subtext="Por revisar/aprobar"
                color="sky"
            />
        </div>
    </div>
</x-app-layout>
