<x-guest-layout>
    <div class="mb-4">
        <h2 class="text-xl font-bold text-gray-900 text-center">Cambio Obligatorio de Contraseña</h2>
        <p class="mt-2 text-sm text-gray-600 text-center">
            Por seguridad institucional, debe actualizar su contraseña temporal antes de continuar al sistema. La nueva contraseña debe tener un mínimo de 10 caracteres e incluir letras y números.
        </p>
    </div>

    <form method="POST" action="{{ route('password.cambiar.update') }}">
        @csrf

        <!-- Contraseña Actual -->
        <div>
            <x-input-label for="current_password" value="Contraseña Actual" />
            <x-text-input id="current_password" class="block mt-1 w-full"
                            type="password"
                            name="current_password"
                            required autocomplete="current-password" autofocus />
            <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
        </div>

        <!-- Nueva Contraseña -->
        <div class="mt-4">
            <x-input-label for="password" value="Nueva Contraseña (mínimo 10 caracteres, letras y números)" />
            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirmar Contraseña -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Confirmar Nueva Contraseña" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation"
                            required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <x-primary-button>
                Guardar Contraseña
            </x-primary-button>
        </div>
    </form>

    <div class="mt-4 pt-4 border-t border-gray-100 flex justify-center">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-gray-500 hover:text-gray-800 underline focus:outline-none">
                Cerrar sesión
            </button>
        </form>
    </div>
</x-guest-layout>
