@props([
    'label' => 'Cuenta',
    'name' => 'cuenta_id',
    'cuentas' => [],
    'value' => null,
    'required' => false,
])

@php
    $cuentasJson = collect($cuentas)->map(function ($c) {
        return [
            'id' => $c->id,
            'codigo' => $c->codigo,
            'nombre' => $c->nombre,
            'display' => $c->codigo . ' · ' . $c->nombre,
        ];
    })->values();

    $cuentaInicial = collect($cuentas)->firstWhere('id', old($name, $value));
    $labelInicial = $cuentaInicial ? $cuentaInicial->codigo . ' · ' . $cuentaInicial->nombre : '';
    $idInicial = $cuentaInicial ? $cuentaInicial->id : old($name, $value);
@endphp

<div
    x-data="{
        cuentas: {{ Js::from($cuentasJson) }},
        query: '',
        open: false,
        selectedId: '{{ $idInicial }}',
        selectedLabel: '{{ addslashes($labelInicial) }}',
        selectedIndex: 0,

        get filteredCuentas() {
            if (!this.query) {
                return this.cuentas;
            }
            const q = this.query.toLowerCase().trim();
            return this.cuentas.filter(c => {
                return c.codigo.toLowerCase().includes(q) || c.nombre.toLowerCase().includes(q);
            });
        },

        selectCuenta(cuenta) {
            if (!cuenta) return;
            this.selectedId = cuenta.id;
            this.selectedLabel = cuenta.display;
            this.query = '';
            this.open = false;

            // Disparar evento para que formularios puedan capturar el cambio
            this.$dispatch('cuenta-selected', cuenta);

            // Mover foco automáticamente al campo de monto si existe
            this.$nextTick(() => {
                const montoInput = document.querySelector('input[name=\'monto\']');
                if (montoInput) {
                    montoInput.focus();
                    montoInput.select();
                }
            });
        },

        selectCurrent() {
            const list = this.filteredCuentas;
            if (list.length > 0 && this.selectedIndex >= 0 && this.selectedIndex < list.length) {
                this.selectCuenta(list[this.selectedIndex]);
            }
        },

        limpiar() {
            this.selectedId = '';
            this.selectedLabel = '';
            this.query = '';
            this.open = true;
            this.$nextTick(() => {
                this.$refs.searchInput.focus();
            });
        }
    }"
    class="relative"
    @click.away="open = false"
>
    <label class="block text-sm font-medium text-slate-700 mb-1">
        {{ $label }}
    </label>

    <!-- Input oculto para envío en formulario -->
    <input type="hidden" name="{{ $name }}" :value="selectedId" {{ $required ? 'required' : '' }}>

    <!-- Vista cuando ya está seleccionada -->
    <div x-show="selectedId && !open" class="flex items-center">
        <div class="flex-1 flex items-center justify-between px-3 py-2 border border-slate-300 rounded-md bg-slate-50 shadow-sm">
            <span class="text-sm font-semibold text-slate-800 font-mono" x-text="selectedLabel"></span>
            <button
                type="button"
                @click="limpiar()"
                class="text-xs text-primary-600 hover:text-primary-800 font-medium px-2 py-1 rounded hover:bg-primary-50 transition"
            >
                Cambiar cuenta
            </button>
        </div>
    </div>

    <!-- Buscador / Selector interactivo por teclado -->
    <div x-show="!selectedId || open" class="relative">
        <div class="relative rounded-md shadow-sm">
            <input
                x-ref="searchInput"
                type="text"
                x-model="query"
                @focus="open = true; selectedIndex = 0"
                @keydown.arrow-down.prevent="if (!open) open = true; selectedIndex = Math.min(selectedIndex + 1, filteredCuentas.length - 1)"
                @keydown.arrow-up.prevent="selectedIndex = Math.max(selectedIndex - 1, 0)"
                @keydown.enter.prevent="if (open) selectCurrent()"
                @keydown.tab="if (open && filteredCuentas.length > 0) { selectCurrent(); }"
                @keydown.escape="open = false"
                placeholder="Escribe código (ej. 001) o nombre de cuenta..."
                autocomplete="off"
                class="w-full text-sm rounded-md border-slate-300 focus:border-primary-500 focus:ring-primary-500 pr-10"
            />
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>

        <!-- Dropdown de resultados -->
        <ul
            x-show="open"
            x-transition
            class="absolute z-50 mt-1 max-h-56 w-full overflow-auto rounded-md bg-white py-1 text-sm shadow-lg ring-1 ring-black ring-opacity-5 border border-slate-200 divide-y divide-slate-100"
            style="display: none;"
        >
            <template x-for="(cuenta, index) in filteredCuentas" :key="cuenta.id">
                <li
                    @click="selectCuenta(cuenta)"
                    @mouseenter="selectedIndex = index"
                    :class="{
                        'bg-primary-50 text-primary-900 font-semibold': selectedIndex === index,
                        'text-slate-700': selectedIndex !== index
                    }"
                    class="cursor-pointer select-none py-2 px-3 flex items-center justify-between transition-colors"
                >
                    <span class="font-mono" x-text="cuenta.display"></span>
                    <span x-show="selectedIndex === index" class="text-xs text-primary-600 font-medium">
                        Presiona Enter o Tab
                    </span>
                </li>
            </template>
            <template x-if="filteredCuentas.length === 0">
                <li class="py-3 px-3 text-center text-xs text-slate-400 italic">
                    No se encontró ninguna cuenta coincidente.
                </li>
            </template>
        </ul>
    </div>

    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
