<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Notificaciones</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Avisos del sistema sobre solicitudes, dictámenes y eventos contables.
                </p>
            </div>
            @if (auth()->user()->unreadNotifications->isNotEmpty())
                <form method="POST" action="{{ route('notificaciones.leer-todas') }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary" size="sm">
                        Marcar todas como leídas
                    </x-ui.button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-4">
        <x-ui.card>
            <div class="divide-y divide-slate-100">
                @forelse ($notificaciones as $n)
                    @php
                        $leida = $n->read_at !== null;
                        $data = $n->data;
                    @endphp
                    <div class="py-4 flex items-start justify-between gap-4 {{ $leida ? 'opacity-70' : 'bg-primary-50/20 -mx-6 px-6' }}">
                        <div class="flex items-start gap-3 min-w-0">
                            <div class="p-2 rounded-full {{ $leida ? 'bg-slate-100 text-slate-500' : 'bg-primary-100 text-primary-700' }} mt-0.5">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 {{ $leida ? '' : 'text-primary-900' }}">
                                    {{ $data['titulo'] ?? 'Notificación' }}
                                </h3>
                                <p class="text-xs text-slate-600 mt-0.5">
                                    {{ $data['mensaje'] ?? '' }}
                                </p>
                                <span class="text-[11px] text-slate-400 font-mono mt-1 block">
                                    {{ $n->created_at->diffForHumans() }} ({{ $n->created_at->format('d/m/Y H:i') }})
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 whitespace-nowrap">
                            @if (! $leida)
                                <form method="POST" action="{{ route('notificaciones.leer', $n->id) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-primary-600 hover:text-primary-800 font-semibold px-2 py-1 rounded hover:bg-primary-50 transition">
                                        Ir al detalle &rarr;
                                    </button>
                                </form>
                            @elseif (isset($data['url']))
                                <a href="{{ $data['url'] }}" class="text-xs text-slate-600 hover:text-slate-900 font-semibold px-2 py-1 rounded hover:bg-slate-100 transition">
                                    Ver &rarr;
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-slate-400 italic">
                        No tienes notificaciones en este momento.
                    </div>
                @endforelse
            </div>

            @if ($notificaciones->hasPages())
                <div class="mt-4 pt-4 border-t border-slate-200">
                    {{ $notificaciones->links() }}
                </div>
            @endif
        </x-ui.card>
    </div>
</x-app-layout>
