@props([
    'label',
    'value',
    'subtext' => null,
    'color' => 'primary'
])

@php
    $colorClasses = match ($color) {
        'success' => 'text-success-600',
        'danger' => 'text-danger-600',
        'sky' => 'text-sky-600',
        default => 'text-primary-600',
    };
@endphp

<div {{ $attributes->merge(['class' => 'bg-white p-6 rounded-lg shadow-sm border border-gray-200']) }}>
    <dt class="text-sm font-medium text-gray-500 truncate">{{ $label }}</dt>
    <dd class="mt-1 text-2xl font-bold tracking-tight {{ $colorClasses }} tabular-nums">{{ $value }}</dd>
    @if($subtext)
        <p class="mt-1 text-xs text-gray-500">{{ $subtext }}</p>
    @endif
</div>
