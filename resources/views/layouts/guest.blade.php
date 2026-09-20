<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Getsemaní Finanzas') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink bg-surface antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary-600 text-white shadow-md mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Iglesia Getsemaní</h1>
                <p class="text-sm text-gray-500 font-medium">Control de Finanzas por Departamentos</p>
            </div>

            <div class="w-full sm:max-w-md px-6 py-6 bg-white shadow-md overflow-hidden rounded-xl border border-gray-200">
                {{ $slot }}
            </div>

            <div class="mt-8 text-center text-xs text-gray-400">
                &copy; {{ date('Y') }} Iglesia Getsemaní · Seminario de Sistemas 1
            </div>
        </div>
    </body>
</html>
