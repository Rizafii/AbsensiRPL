<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">
            Pengaturan Akun Fonnte
        </h2>
    </x-slot>

    @if (session('status'))
        <x-bladewind::alert type="success" class="mb-6">
            {{ session('status') }}
        </x-bladewind::alert>
    @endif

    <div class="max-w-5xl">
        <x-bladewind::card>
            <form method="POST" action="{{ route('settings.fonnte.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <p class="text-sm text-slate-600">
                    Atur akun Fonnte terpisah untuk absensi masuk dan absensi pulang. Jika akun aktif,
                    token dan target grup wajib diisi.
                </p>

                <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    <div class="rounded-lg border border-slate-200 p-4">
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold text-slate-800">Akun Fonnte Absensi Masuk</h3>
                            <p class="mt-1 text-xs text-slate-500">
                                Dipakai untuk notifikasi saat siswa melakukan absensi masuk.
                            </p>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <input type="hidden" name="check_in_is_active" value="0">
                                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                                    <input type="checkbox" name="check_in_is_active" value="1"
                                        class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                        @checked((bool) old('check_in_is_active', $checkInAccount->is_active))>
                                    Akun aktif
                                </label>
                                <x-input-error :messages="$errors->get('check_in_is_active')" class="mt-2" />
                            </div>

                            <div>
                                <x-bladewind::input name="check_in_account_name" label="Nama Akun"
                                    value="{{ old('check_in_account_name', $checkInAccount->account_name) }}" />
                                <x-input-error :messages="$errors->get('check_in_account_name')" class="mt-2" />
                            </div>

                            <div>
                                <x-bladewind::input name="check_in_base_url" label="Base URL Fonnte"
                                    value="{{ old('check_in_base_url', $checkInAccount->base_url) }}" required="true" />
                                <x-input-error :messages="$errors->get('check_in_base_url')" class="mt-2" />
                            </div>

                            <div>
                                <x-bladewind::input name="check_in_token" label="Token Fonnte"
                                    value="{{ old('check_in_token', $checkInAccount->token) }}" />
                                <x-input-error :messages="$errors->get('check_in_token')" class="mt-2" />
                            </div>

                            <div>
                                <x-bladewind::input name="check_in_parent_group_target" label="Target Grup Orang Tua"
                                    value="{{ old('check_in_parent_group_target', $checkInAccount->parent_group_target) }}" />
                                <x-input-error :messages="$errors->get('check_in_parent_group_target')" class="mt-2" />
                            </div>

                            <div>
                                <x-bladewind::input name="check_in_timeout" label="Timeout (detik)" type="number"
                                    value="{{ old('check_in_timeout', $checkInAccount->timeout) }}" required="true" />
                                <x-input-error :messages="$errors->get('check_in_timeout')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-200 p-4">
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold text-slate-800">Akun Fonnte Absensi Pulang</h3>
                            <p class="mt-1 text-xs text-slate-500">
                                Dipakai untuk notifikasi saat siswa melakukan absensi pulang.
                            </p>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <input type="hidden" name="check_out_is_active" value="0">
                                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                                    <input type="checkbox" name="check_out_is_active" value="1"
                                        class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                        @checked((bool) old('check_out_is_active', $checkOutAccount->is_active))>
                                    Akun aktif
                                </label>
                                <x-input-error :messages="$errors->get('check_out_is_active')" class="mt-2" />
                            </div>

                            <div>
                                <x-bladewind::input name="check_out_account_name" label="Nama Akun"
                                    value="{{ old('check_out_account_name', $checkOutAccount->account_name) }}" />
                                <x-input-error :messages="$errors->get('check_out_account_name')" class="mt-2" />
                            </div>

                            <div>
                                <x-bladewind::input name="check_out_base_url" label="Base URL Fonnte"
                                    value="{{ old('check_out_base_url', $checkOutAccount->base_url) }}"
                                    required="true" />
                                <x-input-error :messages="$errors->get('check_out_base_url')" class="mt-2" />
                            </div>

                            <div>
                                <x-bladewind::input name="check_out_token" label="Token Fonnte"
                                    value="{{ old('check_out_token', $checkOutAccount->token) }}" />
                                <x-input-error :messages="$errors->get('check_out_token')" class="mt-2" />
                            </div>

                            <div>
                                <x-bladewind::input name="check_out_parent_group_target" label="Target Grup Orang Tua"
                                    value="{{ old('check_out_parent_group_target', $checkOutAccount->parent_group_target) }}" />
                                <x-input-error :messages="$errors->get('check_out_parent_group_target')" class="mt-2" />
                            </div>

                            <div>
                                <x-bladewind::input name="check_out_timeout" label="Timeout (detik)" type="number"
                                    value="{{ old('check_out_timeout', $checkOutAccount->timeout) }}" required="true" />
                                <x-input-error :messages="$errors->get('check_out_timeout')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <x-bladewind::button icon="check" can_submit="true">
                        Simpan Pengaturan Fonnte
                    </x-bladewind::button>
                </div>
            </form>
        </x-bladewind::card>
    </div>
</x-app-layout>