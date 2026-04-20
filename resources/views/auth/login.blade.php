<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1 block" />
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 group-focus-within:text-indigo-500 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                    </svg>
                </div>
                <x-text-input id="email" class="block w-full pl-10 h-12 bg-gray-50/50 border-gray-200 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all duration-200 rounded-xl" type="email" name="email" :value="old('email')" required autofocus placeholder="nama@email.com" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between mb-1">
                <x-input-label for="password" :value="__('Kata Sandi')" class="text-xs font-semibold text-gray-500 uppercase tracking-wider block" />
                @if (Route::has('password.request'))
                    <a class="text-xs font-medium text-indigo-600 hover:text-indigo-500 transition-colors" href="{{ route('password.request') }}">
                        {{ __('Lupa kata sandi?') }}
                    </a>
                @endif
            </div>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 group-focus-within:text-indigo-500 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <x-text-input id="password" class="block w-full pl-10 h-12 bg-gray-50/50 border-gray-200 focus:bg-white focus:ring-2 focus:ring-indigo-100 transition-all duration-200 rounded-xl"
                                type="password"
                                name="password"
                                required 
                                placeholder="••••••••" />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input id="remember_me" type="checkbox" class="rounded-md border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-4 h-4 transition-all duration-200" name="remember">
                <span class="ms-2 text-sm text-gray-600 font-medium select-none">{{ __('Ingat saya') }}</span>
            </label>
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full h-12 justify-center bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 shadow-lg shadow-indigo-200 border-none rounded-xl text-sm font-bold tracking-normal normal-case transition-all duration-200 transform hover:scale-[1.02]">
                {{ __('Masuk Sekarang') }}
            </x-primary-button>
        </div>

        @if (Route::has('register'))
            <div class="text-center pt-2">
                <p class="text-xs text-gray-400">
                    Belum punya akun? <a href="{{ route('register') }}" class="text-indigo-600 font-semibold hover:underline">Daftar di sini</a>
                </p>
            </div>
        @endif

    </form>
</x-guest-layout>

