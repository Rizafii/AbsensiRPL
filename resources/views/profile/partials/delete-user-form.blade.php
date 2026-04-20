<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-slate-800">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <x-bladewind::button
        color="red"
        onclick="showModal('confirm-user-deletion')"
    >
        {{ __('Delete Account') }}
    </x-bladewind::button>

    <x-bladewind::modal
        name="confirm-user-deletion"
        type="error"
        title="Konfirmasi Penghapusan User"
        show_action_buttons="false">
        <form method="post" action="{{ route('profile.destroy') }}">
            @csrf
            @method('delete')

            <p class="text-sm text-slate-600">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mt-6">
                <x-bladewind::input
                    name="password"
                    type="password"
                    placeholder="{{ __('Password') }}"
                    error_message="{{ $errors->userDeletion->first('password') }}"
                />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-bladewind::button
                    type="secondary"
                    onclick="hideModal('confirm-user-deletion')"
                >
                    {{ __('Cancel') }}
                </x-bladewind::button>

                <x-bladewind::button
                    can_submit="true"
                    color="red"
                >
                    {{ __('Delete Account') }}
                </x-bladewind::button>
            </div>
        </form>
    </x-bladewind::modal>

    @if($errors->userDeletion->isNotEmpty())
        <script>
            window.onload = () => { showModal('confirm-user-deletion'); };
        </script>
    @endif
</section>
