@props([
    'disabled' => false,
    'label' => 'Fecha',
    'name' => 'fecha',
    'value' => null,
])

<div>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }}
        </label>
    @endif
    <input
        type="date"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value ?? date('Y-m-d')) }}"
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->merge(['class' => 'border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm w-full text-sm text-ink']) }}
    >
    @if($name)
        @error($name)
            <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
        @enderror
    @endif
</div>
