@props([
    'disabled' => false,
    'label' => null,
    'name' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => '-- Seleccione una opción --',
])

<div>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }}
        </label>
    @endif
    <select
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->merge(['class' => 'border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm w-full text-sm text-ink']) }}
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach($options as $val => $text)
            <option value="{{ $val }}" {{ (string) old($name, $selected) === (string) $val ? 'selected' : '' }}>
                {{ $text }}
            </option>
        @endforeach
        {{ $slot }}
    </select>
    @if($name)
        @error($name)
            <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
        @enderror
    @endif
</div>
