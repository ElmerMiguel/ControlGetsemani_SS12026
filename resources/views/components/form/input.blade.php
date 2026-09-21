@props([
    'disabled' => false,
    'label' => null,
    'name' => null,
    'type' => 'text',
    'value' => null,
])

<div>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }}
        </label>
    @endif
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->merge(['class' => 'border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm w-full text-sm text-ink']) }}
    >
    @if($name)
        @error($name)
            <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
        @enderror
    @endif
</div>
