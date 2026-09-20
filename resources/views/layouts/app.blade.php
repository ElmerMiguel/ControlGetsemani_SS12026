<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Getsemaní Finanzas') }}</title>

        <!-- Fuentes -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-surface text-ink" x-data="{ sidebarOpen: false }">
        <div class="min-h-screen flex">
            <!-- Backdrop Móvil -->
            <div
                x-show="sidebarOpen"
                @click="sidebarOpen = false"
                x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
                style="display: none;"
            ></div>

            <!-- Sidebar -->
            <aside
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-100 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0 flex flex-col shadow-xl"
            >
                <!-- Cabecera Sidebar -->
                <div class="h-16 flex items-center justify-between px-6 border-b border-slate-800 bg-slate-950">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-lg bg-primary-600 flex items-center justify-center text-white font-bold">
                            G
                        </div>
                        <span class="text-base font-bold tracking-tight text-white">Getsemaní</span>
                    </div>
                    <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Menú de Navegación -->
                <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-1 text-sm font-medium">
                    <!-- Dashboard -->
                    <a
                        href="{{ route('dashboard') }}"
                        class="flex items-center px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-primary-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </a>

                    <div class="pt-3 pb-1 px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        Finanzas
                    </div>

                    @canany(['cajas.gestionar', 'ingresos.ver', 'egresos.ver'])
                        <a
                            href="{{ Route::has('cajas.index') ? route('cajas.index') : '#' }}"
                            class="flex items-center px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('cajas.*') ? 'bg-primary-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                        >
                            <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            Cajas
                        </a>
                    @endcanany

                    @can('ingresos.ver')
                        <a
                            href="{{ Route::has('ingresos.index') ? route('ingresos.index') : '#' }}"
                            class="flex items-center px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('ingresos.*') ? 'bg-primary-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                        >
                            <svg class="w-5 h-5 me-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Ingresos
                        </a>
                    @endcan

                    @can('egresos.ver')
                        <a
                            href="{{ Route::has('egresos.index') ? route('egresos.index') : '#' }}"
                            class="flex items-center px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('egresos.*') ? 'bg-primary-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                        >
                            <svg class="w-5 h-5 me-3 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                            </svg>
                            Egresos
                        </a>
                    @endcan

                    @can('cortes.solicitar')
                        <a
                            href="{{ Route::has('cortes.index') ? route('cortes.index') : '#' }}"
                            class="flex items-center px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('cortes.*') ? 'bg-primary-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                        >
                            <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Cortes de Caja
                        </a>
                    @endcan

                    @can('reportes.ver')
                        <a
                            href="{{ Route::has('reportes.caja') ? route('reportes.caja') : '#' }}"
                            class="flex items-center px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('reportes.*') ? 'bg-primary-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                        >
                            <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Reportes
                        </a>
                    @endcan

                    <div class="pt-3 pb-1 px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        Administración
                    </div>

                    @can('aportantes.gestionar')
                        <a
                            href="{{ Route::has('aportantes.index') ? route('aportantes.index') : '#' }}"
                            class="flex items-center px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('aportantes.*') ? 'bg-primary-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                        >
                            <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Aportantes
                        </a>
                    @endcan

                    @can('catalogos.ver')
                        <a
                            href="{{ Route::has('catalogos.ingresos.index') ? route('catalogos.ingresos.index') : '#' }}"
                            class="flex items-center px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('catalogos.*') ? 'bg-primary-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                        >
                            <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            Catálogos
                        </a>
                    @endcan

                    @can('departamentos.gestionar')
                        <a
                            href="{{ Route::has('departamentos.index') ? route('departamentos.index') : '#' }}"
                            class="flex items-center px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('departamentos.*') ? 'bg-primary-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                        >
                            <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                            </svg>
                            Departamentos
                        </a>
                    @endcan

                    @can('usuarios.gestionar')
                        <a
                            href="{{ Route::has('usuarios.index') ? route('usuarios.index') : '#' }}"
                            class="flex items-center px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('usuarios.*') ? 'bg-primary-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                        >
                            <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            Usuarios
                        </a>
                    @endcan

                    @can('bitacora.ver')
                        <a
                            href="{{ Route::has('bitacora.index') ? route('bitacora.index') : '#' }}"
                            class="flex items-center px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('bitacora.*') ? 'bg-primary-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                        >
                            <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            Bitácora
                        </a>
                    @endcan
                </nav>

                <!-- Pie del Sidebar -->
                <div class="p-4 border-t border-slate-800 text-xs text-slate-400">
                    <p class="font-semibold text-slate-300 truncate">{{ auth()->user()->name }}</p>
                    <p class="truncate">{{ auth()->user()->email }}</p>
                </div>
            </aside>

            <!-- Contenedor Principal -->
            <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
                <!-- Barra Superior (Topbar) -->
                <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center space-x-3">
                        <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <!-- Selector de Caja Activa (§8 y §9.2) -->
                        <div class="hidden sm:flex items-center text-sm">
                            <span class="text-slate-500 text-xs font-medium me-2">Caja activa:</span>
                            @if(isset($cajasDisponibles) && $cajasDisponibles->isNotEmpty())
                                <form method="POST" action="{{ route('caja.activa') }}" class="inline-flex items-center">
                                    @csrf
                                    <select
                                        name="caja_id"
                                        onchange="this.form.submit()"
                                        class="text-xs font-semibold py-1 px-2.5 rounded-md border-primary-300 bg-primary-50 text-primary-800 focus:border-primary-500 focus:ring-primary-500 cursor-pointer shadow-sm"
                                    >
                                        @can('cajas.gestionar')
                                            <option value="todas" @selected(!session('caja_activa_id'))>
                                                Todas las Cajas
                                            </option>
                                        @endcan
                                        @foreach($cajasDisponibles as $cajaDisp)
                                            <option value="{{ $cajaDisp->id }}" @selected((string) session('caja_activa_id') === (string) $cajaDisp->id)>
                                                {{ $cajaDisp->codigo }} &bull; {{ $cajaDisp->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-600">
                                    Sin cajas asignadas
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Lado Derecho: Notificaciones y Usuario -->
                    <div class="flex items-center space-x-4">
                        <!-- Notificaciones (§10) -->
                        <button type="button" class="relative p-1.5 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100">
                            <span class="sr-only">Ver notificaciones</span>
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </button>

                        <!-- Menú de Usuario con Alpine Dropdown -->
                        <div class="relative" x-data="{ userMenuOpen: false }">
                            <button @click="userMenuOpen = !userMenuOpen" class="flex items-center space-x-2 text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none">
                                <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-bold text-xs">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                                </div>
                                <span class="hidden md:inline">{{ auth()->user()->name }}</span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div
                                x-show="userMenuOpen"
                                @click.away="userMenuOpen = false"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="transform opacity-100 scale-100"
                                x-transition:leave-end="transform opacity-0 scale-95"
                                class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 z-50 divide-y divide-gray-100"
                                style="display: none;"
                            >
                                <div class="px-4 py-2 text-xs text-gray-500">
                                    Conectado como <strong class="text-gray-800">{{ auth()->user()->name }}</strong>
                                </div>

                                <div class="py-1">
                                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Mi Perfil
                                    </a>
                                </div>

                                <div class="py-1">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-danger-600 hover:bg-gray-100">
                                            Cerrar sesión
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Encabezado de Página Opcional -->
                @isset($header)
                    <div class="bg-white border-b border-gray-200 px-4 py-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                @endisset

                <!-- Contenido Principal -->
                <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                    <x-ui.flash />
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
