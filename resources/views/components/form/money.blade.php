@props([
    'disabled' => false,
    'label' => 'Monto',
    'name' => 'monto',
    'value' => null,
])

<div>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }}
        </label>
    @endif
    <div class="relative rounded-md shadow-sm">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <span class="text-gray-500 font-bold sm:text-sm">Q</span>
        </div>
        <input
            type="number"
            step="0.01"
            min="0.01"
            name="{{ $name }}"
            id="{{ $name }}"
            value="{{ old($name, $value) }}"
            placeholder="0.00"
            {{ $disabled ? 'disabled' : '' }}
            {{ $attributes->merge(['class' => 'block w-full rounded-md border-gray-300 pl-8 pr-4 text-ink tabular-nums focus:border-primary-500 focus:ring-primary-500 sm:text-sm']) }}
        >
    </div>
    @if($name)
        @error($name)
            <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
        @enderror
    @endif
</div>
