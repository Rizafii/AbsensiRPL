<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800 leading-tight">
            Dashboard
        </h2>
    </x-slot>

    <div class="space-y-8">
        {{-- Header Section --}}
        <x-bladewind::card class="relative overflow-hidden !p-8">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Selamat Datang, {{ Auth::user()->name }}! 👋</h1>
                    <p class="mt-1 text-slate-500">Berikut adalah ringkasan absensi siswa untuk hari ini.</p>
                </div>
            </div>
            {{-- Abstract background shapes can stay as decorative elements --}}
            <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-emerald-50/50 blur-3xl"></div>
            <div class="absolute -left-10 -bottom-10 h-40 w-40 rounded-full bg-blue-50/50 blur-3xl"></div>
        </x-bladewind::card>

        {{-- Statistics Grid --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Total Siswa --}}
            <x-bladewind::card class="hover:shadow-md transition-all duration-300 transform hover:-translate-y-1"
                reduce_padding="true">
                <x-bladewind::statistic number="{{ $totalStudents }}" label="Total Siswa" show_separator="true">
                    <x-slot name="icon">
                        <div class="rounded-xl bg-blue-50 p-3 text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="h-6 w-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                        </div>
                    </x-slot>
                </x-bladewind::statistic>
            </x-bladewind::card>

            {{-- Hadir --}}
            <x-bladewind::card class="hover:shadow-md transition-all duration-300 transform hover:-translate-y-1"
                reduce_padding="true">
                <x-bladewind::statistic number="{{ $presentToday }}" label="Hadir Hari Ini" show_separator="true">
                    <x-slot name="icon">
                        <div class="rounded-xl bg-emerald-50 p-3 text-emerald-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="h-6 w-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </x-slot>
                </x-bladewind::statistic>
            </x-bladewind::card>

            {{-- Telat --}}
            <x-bladewind::card class="hover:shadow-md transition-all duration-300 transform hover:-translate-y-1"
                reduce_padding="true">
                <x-bladewind::statistic number="{{ $lateToday }}" label="Telat" show_separator="true">
                    <x-slot name="icon">
                        <div class="rounded-xl bg-amber-50 p-3 text-amber-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="h-6 w-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </x-slot>
                </x-bladewind::statistic>
            </x-bladewind::card>

            {{-- Belum Absen --}}
            <x-bladewind::card class="hover:shadow-md transition-all duration-300 transform hover:-translate-y-1"
                reduce_padding="true">
                <x-bladewind::statistic number="{{ $notAttended }}" label="Belum Absen" show_separator="true">
                    <x-slot name="icon">
                        <div class="rounded-xl bg-rose-50 p-3 text-rose-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="h-6 w-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                    </x-slot>
                </x-bladewind::statistic>
            </x-bladewind::card>
        </div>
    </div>
</x-app-layout>