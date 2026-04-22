<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />

        <!-- BladewindUI CSS -->
        <link href="{{ asset('vendor/bladewind/css/animate.min.css') }}" rel="stylesheet" />
        <link href="{{ asset('vendor/bladewind/css/bladewind-ui.min.css') }}" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- BladewindUI JS -->
        <script src="{{ asset('vendor/bladewind/js/helpers.js') }}"></script>

        <style>
            [x-cloak] { display: none !important; }
        </style>
    </head>
    <body class="font-sans antialiased bg-slate-50 text-slate-800">
        <div class="min-h-screen flex" x-data="{ mobileOpen: false }">
            {{-- Sidebar --}}
            @include('layouts.sidebar')

            {{-- Main Content Area --}}
            <div class="flex-1 flex flex-col min-h-screen transition-all duration-300 lg:ml-64">
                {{-- Top Bar --}}
                <header class="sticky top-0 z-10 flex h-16 items-center gap-4 border-b border-slate-200 bg-white/80 backdrop-blur-md px-4 sm:px-6 lg:px-8">
                    {{-- Mobile menu toggle --}}
                    <button
                        @click="$dispatch('sidebar-toggle')"
                        class="inline-flex items-center justify-center rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 lg:hidden transition"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    {{-- Page Title --}}
                    @isset($header)
                        <div class="flex-1">
                            {{ $header }}
                        </div>
                    @endisset

                    {{-- Right side actions --}}
                    <div class="flex items-center gap-3">
                        <x-bladewind::icon name="bell" class="h-5 w-5 text-slate-400 cursor-pointer hover:text-slate-600 transition" />
                    </div>
                </header>

                {{-- Page Content --}}
                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>

                {{-- Footer --}}
                <footer class="border-t border-slate-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
                    <p class="text-center text-xs text-slate-400">
                        &copy; {{ date('Y') }} AbsensiRPL &mdash; Sistem Absensi IoT Fingerprint
                    </p>
                </footer>
            </div>
        </div>
    </body>
</html>
