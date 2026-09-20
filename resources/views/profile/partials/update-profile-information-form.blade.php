<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Información del Perfil
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Actualice su nombre público en el sistema. El correo electrónico institucional es gestionado exclusivamente por el Administrador General.
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="Nombre Completo" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Correo Electrónico (Solo lectura)" />
            <x-text-input id="email" type="email" class="mt-1 block w-full bg-gray-100 text-gray-500 cursor-not-allowed" :value="$user->email" disabled />
            <p class="mt-1 text-xs text-gray-500">Para cambiar su correo, solicítelo al Administrador General.</p>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Guardar Cambios</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-green-600"
                >Guardado exitosamente.</p>
            @endif
        </div>
    </form>
</section>
