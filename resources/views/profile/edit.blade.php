<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="max-w-3xl space-y-6">
        <x-bladewind::card>
            @include('profile.partials.update-profile-information-form')
        </x-bladewind::card>

        <x-bladewind::card>
            @include('profile.partials.update-password-form')
        </x-bladewind::card>

        <x-bladewind::card>
            @include('profile.partials.delete-user-form')
        </x-bladewind::card>
    </div>
</x-app-layout>
