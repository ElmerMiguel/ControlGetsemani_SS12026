<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-lg border border-gray-200 shadow-sm bg-white']) }}>
    <table class="min-w-full divide-y divide-gray-200 text-left text-sm text-ink">
        {{ $slot }}
    </table>
</div>
