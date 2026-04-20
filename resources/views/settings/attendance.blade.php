<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Pengaturan Absensi
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <form method="POST" action="{{ route('settings.attendance.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="check_in_time" :value="__('Jam Masuk')" />
                        <x-text-input
                            id="check_in_time"
                            name="check_in_time"
                            type="time"
                            class="mt-1 block w-full"
                            :value="old('check_in_time', \Carbon\Carbon::parse($setting->check_in_time)->format('H:i'))"
                            required
                        />
                        <x-input-error :messages="$errors->get('check_in_time')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="check_out_time" :value="__('Jam Pulang')" />
                        <x-text-input
                            id="check_out_time"
                            name="check_out_time"
                            type="time"
                            class="mt-1 block w-full"
                            :value="old('check_out_time', \Carbon\Carbon::parse($setting->check_out_time)->format('H:i'))"
                            required
                        />
                        <x-input-error :messages="$errors->get('check_out_time')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="late_tolerance" :value="__('Toleransi Telat (menit)')" />
                        <x-text-input
                            id="late_tolerance"
                            name="late_tolerance"
                            type="number"
                            min="0"
                            class="mt-1 block w-full"
                            :value="old('late_tolerance', $setting->late_tolerance)"
                            required
                        />
                        <x-input-error :messages="$errors->get('late_tolerance')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="early_leave_tolerance" :value="__('Toleransi Pulang Cepat (menit)')" />
                        <x-text-input
                            id="early_leave_tolerance"
                            name="early_leave_tolerance"
                            type="number"
                            min="0"
                            class="mt-1 block w-full"
                            :value="old('early_leave_tolerance', $setting->early_leave_tolerance)"
                            required
                        />
                        <x-input-error :messages="$errors->get('early_leave_tolerance')" class="mt-2" />
                    </div>

                    <div>
                        <x-primary-button>
                            Simpan Pengaturan
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
