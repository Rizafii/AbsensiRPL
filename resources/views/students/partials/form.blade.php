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

    <div class="flex items-center gap-3 pt-2">
        <x-bladewind::button can_submit="true" type="primary">
            {{ $submitLabel }}
        </x-bladewind::button>

        <a href="{{ route('students.index') }}">
            <x-bladewind::button type="secondary" color="gray">
                Batal
            </x-bladewind::button>
        </a>
    </div>
</div>
