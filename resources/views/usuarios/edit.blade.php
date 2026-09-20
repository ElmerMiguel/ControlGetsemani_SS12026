<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Editar Usuario: {{ $usuario->name }}</h1>
                <p class="text-sm text-slate-500">Actualiza la información general, rol, cajas asignadas y permisos directos.</p>
            </div>
            <x-ui.button href="{{ route('usuarios.index') }}" variant="secondary">
                Volver a la lista
            </x-ui.button>
        </div>
    </x-slot>

    @php
        $currentRole = old('rol', $usuario->roles->first()?->name ?? 'tesorero');
        $userCajas = old('cajas', $usuario->cajas->pluck('id')->toArray());
        $userPermisos = old('permisos', $usuario->permissions->pluck('name')->toArray());
    @endphp

    <div
        x-data="{
            rol: '{{ $currentRole }}'
        }"
        class="max-w-4xl mx-auto space-y-6"
    >
        <form method="POST" action="{{ route('usuarios.update', $usuario) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Información General -->
            <x-ui.card>
                <h2 class="text-base font-semibold text-slate-900 border-b border-slate-100 pb-3 mb-4">
                    1. Información General
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="name" value="Nombre Completo *" />
                        <x-text-input
                            id="name"
                            name="name"
                            type="text"
                            class="mt-1 block w-full"
                            :value="old('name', $usuario->name)"
                            required
                            autofocus
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <x-input-label for="email" value="Correo Electrónico *" />
                        <x-text-input
                            id="email"
                            name="email"
                            type="email"
                            class="mt-1 block w-full"
                            :value="old('email', $usuario->email)"
                            required
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <div>
                        Estado de cuenta: 
                        @if ($usuario->activo)
                            <span class="text-emerald-700 font-semibold">Activa</span>
                        @else
                            <span class="text-red-600 font-semibold">Inactiva</span>
                        @endif
                    </div>
                    <div>
                        Último acceso: {{ $usuario->last_login_at ? $usuario->last_login_at->translatedFormat('d/m/Y H:i') : 'Sin registros de acceso' }}
                    </div>
                </div>
            </x-ui.card>

            <!-- Rol de Usuario -->
            <x-ui.card>
                <h2 class="text-base font-semibold text-slate-900 border-b border-slate-100 pb-3 mb-4">
                    2. Rol Principal *
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label
                        class="relative flex p-4 border rounded-xl cursor-pointer transition-all focus:outline-none"
                        :class="rol === 'tesorero' ? 'border-primary-600 bg-primary-50/40 ring-2 ring-primary-500/20' : 'border-slate-200 hover:border-slate-300'"
                    >
                        <input
                            type="radio"
                            name="rol"
                            value="tesorero"
                            x-model="rol"
                            class="mt-1 text-primary-600 focus:ring-primary-500"
                        />
                        <div class="ms-3">
                            <span class="block text-sm font-semibold text-slate-900">Tesorero de Departamento</span>
                            <span class="block text-xs text-slate-500 mt-1">Registra ingresos, egresos y solicita cortes de caja únicamente para sus cajas asignadas.</span>
                        </div>
                    </label>

                    <label
                        class="relative flex p-4 border rounded-xl cursor-pointer transition-all focus:outline-none"
                        :class="rol === 'admin' ? 'border-primary-600 bg-primary-50/40 ring-2 ring-primary-500/20' : 'border-slate-200 hover:border-slate-300'"
                    >
                        <input
                            type="radio"
                            name="rol"
                            value="admin"
                            x-model="rol"
                            class="mt-1 text-primary-600 focus:ring-primary-500"
                        />
                        <div class="ms-3">
                            <span class="block text-sm font-semibold text-slate-900">Administrador General</span>
                            <span class="block text-xs text-slate-500 mt-1">Acceso global y supervisión: gestión de usuarios, departamentos, cajas, catálogos y aprobación de cortes.</span>
                        </div>
                    </label>
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('rol')" />
            </x-ui.card>

            <!-- Cajas Asignadas (Solo si es Tesorero) -->
            <x-ui.card x-show="rol === 'tesorero'" x-transition class="space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">3. Cajas Asignadas *</h2>
                        <p class="text-xs text-slate-500">Selecciona las cajas contables que este tesorero administrará (mínimo 1 obligatoria).</p>
                    </div>
                </div>

                <x-input-error class="mt-1" :messages="$errors->get('cajas')" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @forelse ($departamentos as $depto)
                        <div class="p-3 border border-slate-200 rounded-lg bg-slate-50/50">
                            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                {{ $depto->nombre }} ({{ ucfirst($depto->tipo->value ?? $depto->tipo) }})
                            </h3>
                            <div class="space-y-2">
                                @forelse ($depto->cajas as $caja)
                                    <label class="flex items-center text-sm text-slate-700 hover:text-slate-900 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            name="cajas[]"
                                            value="{{ $caja->id }}"
                                            @checked(is_array($userCajas) && in_array($caja->id, $userCajas))
                                            class="rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                                        />
                                        <span class="ms-2 font-medium">{{ $caja->codigo }}</span>
                                        <span class="ms-1 text-slate-500">- {{ $caja->nombre }}</span>
                                        <span class="ms-auto text-xs px-1.5 py-0.5 bg-slate-200 rounded text-slate-600">
                                            {{ ucfirst($caja->medio->value ?? $caja->medio) }}
                                        </span>
                                    </label>
                                @empty
                                    <p class="text-xs text-slate-400 italic">No hay cajas activas registradas.</p>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No hay departamentos activos configurados en el sistema.</p>
                    @endforelse
                </div>
            </x-ui.card>

            <!-- Permisos Directos Adicionales -->
            <x-ui.card>
                <div class="border-b border-slate-100 pb-3 mb-4">
                    <h2 class="text-base font-semibold text-slate-900">4. Permisos Directos Adicionales (Opcional)</h2>
                    <p class="text-xs text-slate-500">Concede permisos individuales específicos adicionales a los otorgados automáticamente por el rol.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach ($permisos as $modulo => $listaPermisos)
                        <div class="p-3 border border-slate-200 rounded-lg bg-white">
                            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 pb-1 border-b border-slate-100">
                                {{ ucfirst($modulo) }}
                            </h3>
                            <div class="space-y-1.5">
                                @foreach ($listaPermisos as $p)
                                    <label class="flex items-center text-xs text-slate-700 hover:text-slate-900 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            name="permisos[]"
                                            value="{{ $p->name }}"
                                            @checked(is_array($userPermisos) && in_array($p->name, $userPermisos))
                                            class="rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                                        />
                                        <span class="ms-2 font-mono text-[11px]">{{ $p->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            <div class="flex items-center justify-end gap-3 pt-4">
                <x-ui.button href="{{ route('usuarios.index') }}" variant="secondary">
                    Cancelar
                </x-ui.button>
                <x-ui.button type="submit" variant="primary">
                    Guardar Cambios
                </x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
