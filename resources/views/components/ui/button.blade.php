@props([
    'variant' => 'primary',
    'type' => 'button',
])

@php
    $baseClasses = 'inline-flex items-center px-4 py-2 border rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm';
    
    $variants = [
        'primary' => 'border-transparent bg-primary-600 text-white hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:ring-primary-500',
        'secondary' => 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:bg-gray-50 active:bg-gray-100 focus:ring-primary-500',
        'danger' => 'border-transparent bg-danger-600 text-white hover:bg-danger-700 focus:bg-danger-700 active:bg-danger-900 focus:ring-danger-500',
        'success' => 'border-transparent bg-success-600 text-white hover:bg-success-700 focus:bg-success-700 active:bg-success-900 focus:ring-success-500',
    ];

    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
