@props(['active' => ''])

@php
    $currentUser = Auth::user();
    $isAdmin = $currentUser?->isAdmin() ?? false;
    $isStudent = $currentUser?->isStudent() ?? false;
    $homeRoute = $isStudent ? route('student.attendance.dashboard') : route('dashboard');
@endphp

<aside @sidebar-toggle.window="mobileOpen = !mobileOpen"
    class="fixed inset-y-0 left-0 z-[99] flex flex-col w-64 bg-white border-r border-slate-200 text-slate-600 transition-transform duration-300 transform lg:translate-x-0"
    :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'" id="sidebar">
    {{-- Logo / Brand --}}
    <div class="relative flex h-48 items-center justify-center px-2 border-b border-slate-100">
        <a href="{{ $homeRoute }}" class="flex items-center justify-center overflow-hidden w-full">
            <img src="{{ asset('favicon.svg') }}" alt="Logo"
                class="h-40 w-full object-contain transition-all duration-300">
        </a>
        {{-- Close button for mobile --}}
        <button @click="mobileOpen = false"
            class="absolute top-4 right-4 p-2 text-slate-400 hover:text-slate-600 lg:hidden">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 space-y-1.5 p-3 overflow-y-auto">
        <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-widest text-slate-400">
            Menu Utama
        </p>

        @if ($isAdmin)
            <a href="{{ route('dashboard') }}"
                class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200
                        {{ request()->routeIs('dashboard') ? 'bg-[#2D5A43] text-white shadow-md shadow-emerald-900/10' : 'text-slate-600 hover:bg-emerald-50 hover:text-[#2D5A43]' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-5 w-5 shrink-0 transition-colors">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                </svg>
                <span class="whitespace-nowrap">Dashboard</span>
            </a>

            <a href="{{ route('students.index') }}"
                class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200
                        {{ request()->routeIs('students.*') ? 'bg-[#2D5A43] text-white shadow-md shadow-emerald-900/10' : 'text-slate-600 hover:bg-emerald-50 hover:text-[#2D5A43]' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-5 w-5 shrink-0 transition-colors">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
                <span class="whitespace-nowrap">Siswa</span>
            </a>

            <a href="{{ route('reports.attendance') }}"
                class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200
                        {{ request()->routeIs('reports.*') ? 'bg-[#2D5A43] text-white shadow-md shadow-emerald-900/10' : 'text-slate-600 hover:bg-emerald-50 hover:text-[#2D5A43]' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-5 w-5 shrink-0 transition-colors">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                </svg>
                <span class="whitespace-nowrap">Laporan</span>
            </a>

            <a href="{{ route('enroll.index') }}"
                class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200
                        {{ request()->routeIs('enroll.*') ? 'bg-[#2D5A43] text-white shadow-md shadow-emerald-900/10' : 'text-slate-600 hover:bg-emerald-50 hover:text-[#2D5A43]' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-5 w-5 shrink-0 transition-colors">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M7.864 4.243A7.5 7.5 0 0119.5 10.5c0 2.92-.556 5.709-1.568 8.268M5.742 6.364A7.465 7.465 0 004.5 10.5a7.464 7.464 0 01-1.15 3.993m1.989 3.559A11.209 11.209 0 008.25 10.5a3.75 3.75 0 117.5 0c0 .527-.021 1.049-.064 1.565M12 10.5a14.94 14.94 0 01-3.6 9.75m6.633-4.596a18.666 18.666 0 01-2.485 5.33" />
                </svg>
                <span class="whitespace-nowrap">Enroll</span>
            </a>

            <div class="my-4 border-t border-slate-100"></div>

            <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                Pengaturan
            </p>

            <a href="{{ route('settings.attendance.edit') }}"
                class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200
                        {{ request()->routeIs('settings.attendance.*') ? 'bg-[#2D5A43] text-white shadow-md shadow-emerald-900/10' : 'text-slate-600 hover:bg-emerald-50 hover:text-[#2D5A43]' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-5 w-5 shrink-0 transition-colors">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="whitespace-nowrap">Pengaturan Absensi</span>
            </a>

            <a href="{{ route('settings.fonnte.edit') }}"
                class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200
                        {{ request()->routeIs('settings.fonnte.*') ? 'bg-[#2D5A43] text-white shadow-md shadow-emerald-900/10' : 'text-slate-600 hover:bg-emerald-50 hover:text-[#2D5A43]' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-5 w-5 shrink-0 transition-colors">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.25 12c0-1.232.565-2.333 1.449-3.055A4.48 4.48 0 003.75 7.5a4.5 4.5 0 019 0c0 .314-.032.62-.093.914.745.273 1.42.71 1.968 1.273a3.75 3.75 0 016.375 2.313A3.75 3.75 0 0117.25 15.75H8.25A6 6 0 012.25 12z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 12h7.5M8.25 15h4.5" />
                </svg>
                <span class="whitespace-nowrap">Pengaturan Fonnte</span>
            </a>
        @endif

        @if ($isStudent)
            <a href="{{ route('student.attendance.dashboard') }}"
                class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200
                        {{ request()->routeIs('student.attendance.*') ? 'bg-[#2D5A43] text-white shadow-md shadow-emerald-900/10' : 'text-slate-600 hover:bg-emerald-50 hover:text-[#2D5A43]' }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-5 w-5 shrink-0 transition-colors">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="whitespace-nowrap">Dashboard Absensi</span>
            </a>
        @endif

        <div class="my-4 border-t border-slate-100"></div>

        <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-widest text-slate-400">
            Akun
        </p>

        <a href="{{ route('profile.edit') }}"
            class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200
                  {{ request()->routeIs('profile.*') ? 'bg-[#2D5A43] text-white shadow-md shadow-emerald-900/10' : 'text-slate-600 hover:bg-emerald-50 hover:text-[#2D5A43]' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="h-5 w-5 shrink-0 transition-colors">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
            </svg>
            <span class="whitespace-nowrap">Profile</span>
        </a>
    </nav>

    {{-- User Info / Logout --}}
    <div class="border-t border-slate-100 p-3">
        <div class="flex items-center gap-3 rounded-xl px-3 py-2.5 bg-slate-50 border border-slate-100">
            <div
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white text-xs font-bold text-slate-600 shadow-sm uppercase">
                {{ substr(Auth::user()->name, 0, 2) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-bold text-slate-800">{{ Auth::user()->name }}</p>
                <p class="truncate text-[10px] text-slate-400 font-medium">{{ Auth::user()->email }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="mt-2 flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-500 hover:bg-red-50 hover:text-red-600 transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-5 w-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                </svg>
                <span class="whitespace-nowrap">Logout</span>
            </button>
        </form>
    </div>
</aside>

{{-- Mobile Overlay --}}
<div x-show="mobileOpen" x-cloak @click="mobileOpen = false"
    class="fixed inset-0 z-40 bg-black/50 lg:hidden"
    x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-300"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>