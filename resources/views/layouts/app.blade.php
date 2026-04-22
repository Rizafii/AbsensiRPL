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
                <header class="sticky top-0 z-50 flex h-16 items-center gap-4 border-b border-slate-200 bg-white px-4 sm:px-6 lg:px-8">
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
                    @if(auth()->check() && auth()->user()->isAdmin())
                        <div x-data="notificationComponent()" class="flex items-center gap-3 relative" x-init="init()">
                            <button @click="openModal" class="relative">
                                <x-bladewind::icon name="bell" class="h-6 w-6 text-slate-400 cursor-pointer hover:text-slate-600 transition" />
                                <span x-show="unreadCount > 0" x-text="unreadCount" class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white" style="display: none;"></span>
                            </button>

                            <!-- Notification Modal -->
<div x-show="isOpen" class="absolute right-0 top-full mt-2 z-50 w-80 sm:w-96" style="display: none;" x-transition>
    <div @click.outside="isOpen = false" class="bg-white rounded-lg shadow-xl overflow-hidden flex flex-col max-h-[80vh]">
                                    <div class="px-4 py-3 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                                        <h3 class="font-semibold text-slate-800">Notifikasi</h3>
                                        <button @click="isOpen = false" class="text-slate-400 hover:text-slate-600">
                                            <x-bladewind::icon name="x-mark" class="h-5 w-5" />
                                        </button>
                                    </div>
                                    <div class="overflow-y-auto flex-1 p-4">
                                        <template x-if="notifications.length === 0">
                                            <div class="flex flex-col items-center justify-center py-8 text-slate-500">
                                                <x-bladewind::icon name="bell-slash" class="h-10 w-10 mb-2 opacity-20" />
                                                <p class="text-sm">Belum ada notifikasi.</p>
                                            </div>
                                        </template>
                                        <div class="space-y-3">
                                            <template x-for="notif in notifications" :key="notif.id">
                                                <div class="p-3 rounded-lg border flex gap-3 items-start" :class="notif.read_at ? 'bg-white border-slate-200' : 'bg-blue-50 border-blue-200'">
                                                    <div class="mt-1">
                                                        <template x-if="notif.data.type === 'check-in'">
                                                            <div class="bg-green-100 text-green-600 p-2 rounded-full">
                                                                <x-bladewind::icon name="arrow-right-end-on-rectangle" class="h-4 w-4" />
                                                            </div>
                                                        </template>
                                                        <template x-if="notif.data.type === 'check-out'">
                                                            <div class="bg-orange-100 text-orange-600 p-2 rounded-full">
                                                                <x-bladewind::icon name="arrow-left-start-on-rectangle" class="h-4 w-4" />
                                                            </div>
                                                        </template>
                                                        <template x-if="!notif.data.type">
                                                            <div class="bg-slate-100 text-slate-500 p-2 rounded-full">
                                                                <x-bladewind::icon name="bell" class="h-4 w-4" />
                                                            </div>
                                                        </template>
                                                    </div>
                                                    <div class="flex-1">
                                                        <template x-if="notif.data.student_name">
                                                            <div>
                                                                <p class="text-sm text-slate-800">
                                                                    <span class="font-bold" x-text="notif.data.student_name"></span> 
                                                                    <span x-text="notif.data.type === 'check-in' ? 'melakukan absensi masuk' : 'melakukan absensi pulang'"></span>
                                                                </p>
                                                                <div class="mt-1 flex items-center gap-2">
                                                                    <span class="text-xs px-2 py-0.5 rounded-full border" 
                                                                        :class="{
                                                                            'bg-green-50 text-green-700 border-green-200': notif.data.status === 'Tepat Waktu' || notif.data.status === 'Hadir',
                                                                            'bg-red-50 text-red-700 border-red-200': notif.data.status === 'Terlambat',
                                                                            'bg-yellow-50 text-yellow-700 border-yellow-200': notif.data.status === 'Pulang Awal'
                                                                        }"
                                                                        x-text="notif.data.status"></span>
                                                                    <span class="text-xs text-slate-400" x-text="formatDate(notif.data.time)"></span>
                                                                </div>
                                                            </div>
                                                        </template>
                                                        <template x-if="!notif.data.student_name">
                                                            <div>
                                                                <p class="text-sm text-slate-800" x-text="notif.data.message || 'Notifikasi dari sistem.'"></p>
                                                                <p class="text-xs text-slate-400 mt-1" x-text="formatDate(notif.created_at)"></p>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="px-4 py-3 border-t border-slate-200 bg-slate-50 text-center" x-show="unreadCount > 0">
                                        <button @click="markAsRead" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                            Tandai semua sudah dibaca
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                            document.addEventListener('alpine:init', () => {
                                Alpine.data('notificationComponent', () => ({
                                    isOpen: false,
                                    notifications: [],
                                    unreadCount: 0,
                                    
                                    init() {
                                        this.fetchNotifications();
                                        setInterval(() => {
                                            if(!this.isOpen) this.fetchNotifications();
                                        }, 10000);
                                    },
                                    
                                    async fetchNotifications() {
                                        try {
                                            const response = await fetch('{{ route('notifications.index') }}', {
                                                headers: {
                                                    'Accept': 'application/json',
                                                    'X-Requested-With': 'XMLHttpRequest'
                                                }
                                            });
                                            if (response.ok) {
                                                const data = await response.json();
                                                this.notifications = data.notifications;
                                                this.unreadCount = data.unread_count;
                                            }
                                        } catch (error) {
                                            console.error('Error fetching notifications:', error);
                                        }
                                    },
                                    
                                    openModal() {
                                        this.isOpen = true;
                                        if (this.unreadCount > 0) {
                                            this.markAsRead();
                                        }
                                    },
                                    
                                    async markAsRead() {
                                        if (this.unreadCount === 0) return;
                                        
                                        this.unreadCount = 0;
                                        this.notifications = this.notifications.map(n => ({ ...n, read_at: new Date().toISOString() }));
                                        
                                        try {
                                            await fetch('{{ route('notifications.markRead') }}', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'Accept': 'application/json',
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                                }
                                            });
                                        } catch (error) {
                                            console.error('Error marking as read:', error);
                                        }
                                    },
                                    
                                    formatDate(dateString) {
                                        const date = new Date(dateString);
                                        return date.toLocaleString('id-ID', {
                                            day: 'numeric', month: 'short', year: 'numeric',
                                            hour: '2-digit', minute: '2-digit'
                                        });
                                    }
                                }));
                            });
                        </script>
                    @endif
                </header>

                {{-- Page Content --}}
                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>

                {{-- Footer --}}
                <footer class="border-t border-slate-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
                    <p class="text-center text-xs text-slate-400">
                        &copy; {{ date('Y') }}  &mdash; SiHadir
                    </p>
                </footer>
            </div>
        </div>
    </body>
</html>
