<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Crear Nuevo Usuario</h1>
                <p class="text-sm text-slate-500">Registra una nueva cuenta de acceso asignando rol, cajas y permisos directos.</p>
            </div>
            <x-ui.button href="{{ route('usuarios.index') }}" variant="secondary">
                Volver a la lista
            </x-ui.button>
        </div>
    </x-slot>

    <div
        x-data="{
            rol: '{{ old('rol', 'tesorero') }}',
            password: '{{ old('password', '') }}',
            generarClave() {
                const letrasMayus = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
                const letrasMinus = 'abcdefghjkmnpqrstuvwxyz';
                const numeros = '23456789';
                const simbolos = '!@#$%&*';
                const todos = letrasMayus + letrasMinus + numeros + simbolos;
                
                let clave = '';
                clave += letrasMayus[Math.floor(Math.random() * letrasMayus.length)];
                clave += letrasMinus[Math.floor(Math.random() * letrasMinus.length)];
                clave += numeros[Math.floor(Math.random() * numeros.length)];
                clave += simbolos[Math.floor(Math.random() * simbolos.length)];
                
                for (let i = 4; i < 12; i++) {
                    clave += todos[Math.floor(Math.random() * todos.length)];
                }
                
                this.password = clave.split('').sort(() => 0.5 - Math.random()).join('');
            }
        }"
        class="max-w-4xl mx-auto space-y-6"
    >
        <form method="POST" action="{{ route('usuarios.store') }}" class="space-y-6">
            @csrf

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
                            :value="old('name')"
                            required
                            autofocus
                            placeholder="Ej. Hermano Carlos Gómez"
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
                            :value="old('email')"
                            required
                            placeholder="ejemplo@getsemani.test"
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>
                </div>

                <!-- Contraseña Temporal -->
                <div class="mt-6 pt-4 border-t border-slate-100">
                    <x-input-label for="password" value="Contraseña Temporal Inicial" />
                    <div class="mt-1 flex gap-2">
                        <x-text-input
                            id="password"
                            name="password"
                            type="text"
                            class="block w-full font-mono text-sm"
                            x-model="password"
                            placeholder="Se generará una contraseña segura automáticamente"
                        />
                        <x-ui.button
                            type="button"
                            variant="secondary"
                            class="shrink-0"
                            @click="generarClave()"
                        >
                            <svg class="w-4 h-4 me-1.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                            Generar
                        </x-ui.button>
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500">
                        Si dejas este campo vacío, el servidor generará una clave aleatoria de 12 caracteres. Se mostrará en pantalla una sola vez al guardar y el usuario deberá cambiarla obligatoriamente en su primer acceso (RN-14).
                    </p>
                    <x-input-error class="mt-2" :messages="$errors->get('password')" />
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
                                            @checked(is_array(old('cajas')) && in_array($caja->id, old('cajas')))
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
                                            @checked(is_array(old('permisos')) && in_array($p->name, old('permisos')))
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
                    Guardar Usuario
                </x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
