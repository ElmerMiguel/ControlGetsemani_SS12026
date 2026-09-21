@props([
    'variant' => 'neutral',
])

@php
    $variants = [
        'neutral' => 'bg-gray-100 text-gray-800 border-gray-200',
        'success' => 'bg-green-100 text-green-800 border-green-200',
        'danger' => 'bg-red-100 text-red-800 border-red-200',
        'warning' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'info' => 'bg-sky-100 text-sky-800 border-sky-200',
    ];

    $classes = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ' . ($variants[$variant] ?? $variants['neutral']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
