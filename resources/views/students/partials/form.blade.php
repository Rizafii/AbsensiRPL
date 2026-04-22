@csrf

<div class="space-y-4">
    <div>
        <x-bladewind::input
            name="name"
            label="Nama Siswa"
            required="true"
            value="{{ old('name', $student->name ?? '') }}"
            error_message="{{ $errors->first('name') }}"
        />
    </div>

    <div>
        <x-bladewind::input
            name="nis"
            label="NIS"
            required="true"
            value="{{ old('nis', $student->nis ?? '') }}"
            error_message="{{ $errors->first('nis') }}"
        />
    </div>

    <div>
        <x-bladewind::input
            name="fingerprint_id"
            label="Fingerprint ID"
            type="number"
            required="true"
            value="{{ old('fingerprint_id', $student->fingerprint_id ?? '') }}"
            error_message="{{ $errors->first('fingerprint_id') }}"
        />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-bladewind::input
                name="email"
                label="Email Login"
                required="true"
                value="{{ old('email', $student?->user?->email ?? '') }}"
                error_message="{{ $errors->first('email') }}"
                readonly="{{ is_null($student) ? 'true' : 'false' }}"
                id="email-field"
            />
        </div>

        <div>
            <x-bladewind::input
                name="password"
                label="Password"
                type="text"
                required="{{ is_null($student) ? 'true' : 'false' }}"
                value=""
                error_message="{{ $errors->first('password') }}"
                readonly="{{ is_null($student) ? 'true' : 'false' }}"
                id="password-field"
                placeholder="{{ !is_null($student) ? 'Kosongkan jika tidak ganti' : '' }}"
            />
        </div>
    </div>

    @if(is_null($student))
    <div class="flex justify-start">
        <x-bladewind::button
            name="btn-generate"
            type="secondary"
            size="tiny"
            onclick="generateCredentials()"
            class="!rounded-xl"
        >
            Generate Email & Password
        </x-bladewind::button>
    </div>

    <script>
        function generateCredentials() {
            const nis = document.getElementsByName('nis')[0].value;
            if (!nis) {
                alert('Silakan isi NIS terlebih dahulu');
                return;
            }
            // Format: nis@absensi.rpl
            const cleanNis = nis.toLowerCase().replace(/[^a-z0-9]/g, '');
            document.getElementById('email-field').value = cleanNis + '@absensi.rpl';
            document.getElementById('password-field').value = nis;
        }
    </script>
    @endif

    <div class="flex items-center gap-3 pt-4 border-t border-slate-50">
        <x-bladewind::button can_submit="true" type="primary" class="!rounded-xl">
            {{ $submitLabel }}
        </x-bladewind::button>

        <a href="{{ route('students.index') }}">
            <x-bladewind::button type="secondary" color="gray" class="!rounded-xl">
                Batal
            </x-bladewind::button>
        </a>
    </div>
</div>
