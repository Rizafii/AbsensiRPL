<x-app-layout>
    <div class="max-w-3xl space-y-6">
        {{-- Header --}}
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100">
                <x-bladewind::icon name="user-plus" class="h-5 w-5" />
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Tambah Siswa</h1>
                <p class="text-sm text-slate-500">Daftarkan siswa baru ke dalam sistem absensi.</p>
            </div>
        </div>

        <x-bladewind::card class="!rounded-2xl border-slate-100 shadow-sm">
            <form method="POST" action="{{ route('students.store') }}">
                @include('students.partials.form', [
                    'submitLabel' => 'Simpan Siswa',
                    'student' => null,
                ])
            </form>
        </x-bladewind::card>
    </div>
</x-app-layout>
