@props(['title' => null, 'action' => null])

<div {{ $attributes->merge(['class' => 'bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200']) }}>
    @if($title || $action)
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            @if($title)
                <h3 class="text-base font-semibold text-ink">{{ $title }}</h3>
            @endif
            @if($action)
                <div>{{ $action }}</div>
            @endif
        </div>
    @endif
    <div class="p-6 text-ink">
        {{ $slot }}
    </div>
</div>
