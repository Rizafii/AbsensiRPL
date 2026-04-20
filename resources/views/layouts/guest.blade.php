<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-50 relative overflow-hidden">
            <!-- Background Decoration -->
            <div class="absolute inset-0 z-0">
                <img src="{{ asset('assets/images/login-bg.png') }}" class="w-full h-full object-cover opacity-20 filter blur-sm scale-105" alt="">
                <div class="absolute inset-0 bg-gradient-to-tr from-white via-white/80 to-transparent"></div>
            </div>

            <div class="relative z-10 w-full sm:max-w-md px-6 py-12">
                <div class="flex flex-col items-center mb-8">
                    <a href="/" class="transition-transform duration-300 hover:scale-110">
                        <x-application-logo class="h-20 w-auto" />
                    </a>


                    <h1 class="mt-4 text-2xl font-bold text-gray-900 tracking-tight">Selamat Datang</h1>
                    <p class="text-sm text-gray-500 mt-1">Silakan masuk ke akun Anda</p>
                </div>

                <div class="w-full px-8 py-10 bg-white/70 backdrop-blur-xl border border-white shadow-2xl shadow-gray-200/50 overflow-hidden rounded-3xl">
                    {{ $slot }}
                </div>
                
                <p class="mt-8 text-center text-xs text-gray-400">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </p>
            </div>
        </div>
    </body>
</html>

