<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800 leading-tight">
            Pengaturan Absensi
        </h2>
    </x-slot>

    @if (session('status'))
        <x-bladewind::alert type="success" class="mb-6">
            {{ session('status') }}
        </x-bladewind::alert>
    @endif

    <div class="max-w-3xl">
        <x-bladewind::card>
            <form method="POST" action="{{ route('settings.attendance.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <x-bladewind::input
                        name="check_in_time"
                        label="Jam Masuk"
                        type="time"
                        value="{{ old('check_in_time', \Carbon\Carbon::parse($setting->check_in_time)->format('H:i')) }}"
                        required="true"
                    />
                    <x-input-error :messages="$errors->get('check_in_time')" class="mt-2" />
                </div>

                <div>
                    <x-bladewind::input
                        name="check_out_time"
                        label="Jam Pulang"
                        type="time"
                        value="{{ old('check_out_time', \Carbon\Carbon::parse($setting->check_out_time)->format('H:i')) }}"
                        required="true"
                    />
                    <x-input-error :messages="$errors->get('check_out_time')" class="mt-2" />
                </div>

                <div>
                    <x-bladewind::input
                        name="late_tolerance"
                        label="Toleransi Telat (menit)"
                        type="number"
                        value="{{ old('late_tolerance', $setting->late_tolerance) }}"
                        required="true"
                    />
                    <x-input-error :messages="$errors->get('late_tolerance')" class="mt-2" />
                </div>

                <div>
                    <x-bladewind::input
                        name="early_leave_tolerance"
                        label="Toleransi Pulang Cepat (menit)"
                        type="number"
                        value="{{ old('early_leave_tolerance', $setting->early_leave_tolerance) }}"
                        required="true"
                    />
                    <x-input-error :messages="$errors->get('early_leave_tolerance')" class="mt-2" />
                </div>

                <div>
                    <x-bladewind::button icon="check" can_submit="true">
                        Simpan Pengaturan
                    </x-bladewind::button>
                </div>
            </form>
        </x-bladewind::card>
    </div>
</x-app-layout>
