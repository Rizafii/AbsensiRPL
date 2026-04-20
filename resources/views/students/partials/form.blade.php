@csrf

<div class="space-y-5">
    <div>
        <x-input-label for="name" :value="__('Nama Siswa')" />
        <x-text-input
            id="name"
            name="name"
            type="text"
            class="mt-1 block w-full"
            :value="old('name', $student->name ?? '')"
            required
            autofocus
        />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="nis" :value="__('NIS')" />
        <x-text-input
            id="nis"
            name="nis"
            type="text"
            class="mt-1 block w-full"
            :value="old('nis', $student->nis ?? '')"
            required
        />
        <x-input-error :messages="$errors->get('nis')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="fingerprint_id" :value="__('Fingerprint ID')" />
        <x-text-input
            id="fingerprint_id"
            name="fingerprint_id"
            type="number"
            min="1"
            class="mt-1 block w-full"
            :value="old('fingerprint_id', $student->fingerprint_id ?? '')"
            required
        />
        <x-input-error :messages="$errors->get('fingerprint_id')" class="mt-2" />
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>
            {{ $submitLabel }}
        </x-primary-button>

        <a
            href="{{ route('students.index') }}"
            class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 transition hover:bg-gray-100"
        >
            Batal
        </a>
    </div>
</div>
