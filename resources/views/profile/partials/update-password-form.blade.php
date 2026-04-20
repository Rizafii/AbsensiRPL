<section>
    <header>
        <h2 class="text-lg font-medium text-slate-800">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('put')

        <div>
            <x-bladewind::input
                name="current_password"
                label="Current Password"
                type="password"
                required="true"
                error_message="{{ $errors->updatePassword->first('current_password') }}"
            />
        </div>

        <div>
            <x-bladewind::input
                name="password"
                label="New Password"
                type="password"
                required="true"
                error_message="{{ $errors->updatePassword->first('password') }}"
            />
        </div>

        <div>
            <x-bladewind::input
                name="password_confirmation"
                label="Confirm Password"
                type="password"
                required="true"
                error_message="{{ $errors->updatePassword->first('password_confirmation') }}"
            />
        </div>

        <div class="flex items-center gap-4 pt-2">
            <x-bladewind::button can_submit="true" type="primary">
                {{ __('Save') }}
            </x-bladewind::button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-slate-500 font-medium"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
