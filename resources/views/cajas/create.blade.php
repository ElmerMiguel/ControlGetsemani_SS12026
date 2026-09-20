<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Aperturar Nueva Caja</h1>
                <p class="text-sm text-slate-500">Registra una caja contable o cuenta bancaria y asigna sus tesoreros responsables.</p>
            </div>
            <x-ui.button href="{{ route('cajas.index') }}" variant="secondary">
                Volver a la lista
            </x-ui.button>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <x-ui.card>
            <form method="POST" action="{{ route('cajas.store') }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-form.select
                            label="Departamento *"
                            name="departamento_id"
                            required
                        >
                            @foreach ($departamentos as $depto)
                                <option value="{{ $depto->id }}" @selected(old('departamento_id') == $depto->id)>
                                    {{ $depto->nombre }} ({{ ucfirst($depto->tipo->value) }})
                                </option>
                            @endforeach
                        </x-form.select>
                    </div>

                    <div>
                        <x-form.input
                            label="Código Único *"
                            name="codigo"
                            placeholder="Ej. CJ-001 o BAN-01"
                            required
                        />
                    </div>

                    <div class="md:col-span-2">
                        <x-form.input
                            label="Nombre de la Caja *"
                            name="nombre"
                            placeholder="Ej. Caja General o Banco Industrial - Cuenta Principal"
                            required
                        />
                    </div>

                    <div>
                        <x-form.select
                            label="Medio de Custodia *"
                            name="medio"
                            required
                        >
                            @foreach ($medios as $medio)
                                <option value="{{ $medio->value }}" @selected(old('medio') === $medio->value)>
                                    {{ ucfirst($medio->value) }}
                                </option>
                            @endforeach
                        </x-form.select>
                    </div>

                    <div>
                        <x-form.date
                            label="Fecha de Apertura *"
                            name="fecha_apertura"
                            :value="old('fecha_apertura', date('Y-m-d'))"
                            required
                        />
                    </div>

                    <div>
                        <x-form.money
                            label="Saldo Inicial / de Apertura *"
                            name="saldo_apertura"
                            :value="old('saldo_apertura', '0.00')"
                            required
                        />
                    </div>

                    <div class="flex items-center pt-6">
                        <label class="relative flex items-center gap-3 cursor-pointer">
                            <input
                                type="checkbox"
                                name="activa"
                                value="1"
                                class="rounded border-slate-300 text-primary-600 focus:ring-primary-500 w-4 h-4"
                                @checked(old('activa', true))
                            />
                            <div>
                                <span class="text-sm font-medium text-slate-800">Caja activa</span>
                                <p class="text-xs text-slate-500">Permitirá registrar ingresos y egresos inmediatamente.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Selección de Tesoreros -->
                <div class="pt-6 border-t border-slate-200">
                    <h2 class="text-base font-semibold text-slate-900 mb-1">Tesoreros Asignados</h2>
                    <p class="text-xs text-slate-500 mb-4">Selecciona los usuarios autorizados para operar esta caja.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-56 overflow-y-auto p-3 bg-slate-50 rounded-lg border border-slate-200">
                        @forelse ($tesoreros as $tesorero)
                            <label class="flex items-center p-2 rounded-md hover:bg-white transition-colors cursor-pointer border border-transparent hover:border-slate-200">
                                <input
                                    type="checkbox"
                                    name="tesoreros[]"
                                    value="{{ $tesorero->id }}"
                                    class="rounded border-slate-300 text-primary-600 focus:ring-primary-500 w-4 h-4 me-3"
                                    @checked(in_array($tesorero->id, old('tesoreros', [])))
                                />
                                <div>
                                    <div class="text-sm font-medium text-slate-800">{{ $tesorero->name }}</div>
                                    <div class="text-xs text-slate-400">{{ $tesorero->email }}</div>
                                </div>
                            </label>
                        @empty
                            <div class="col-span-2 text-xs text-slate-400 italic p-2">
                                No hay tesoreros activos registrados. Puedes crearlos en el módulo de usuarios.
                            </div>
                        @endforelse
                    </div>
                    @error('tesoreros')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200">
                    <x-ui.button href="{{ route('cajas.index') }}" variant="secondary">
                        Cancelar
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Registrar Caja
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-app-layout>
