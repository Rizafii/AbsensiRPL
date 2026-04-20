<section>
    <header>
        <h2 class="text-lg font-medium text-slate-800">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('patch')

        <div>
            <x-bladewind::input
                name="name"
                label="Name"
                required="true"
                value="{{ old('name', $user->name) }}"
                error_message="{{ $errors->first('name') }}"
            />
        </div>

        <div>
            <x-bladewind::input
                name="email"
                label="Email"
                type="email"
                required="true"
                value="{{ old('email', $user->email) }}"
                error_message="{{ $errors->first('email') }}"
            />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-slate-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-slate-500 hover:text-slate-800 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-2">
            <x-bladewind::button can_submit="true" type="primary">
                {{ __('Save') }}
            </x-bladewind::button>

            @if (session('status') === 'profile-updated')
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
