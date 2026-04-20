<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tambah Siswa
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <form method="POST" action="{{ route('students.store') }}">
                    @include('students.partials.form', [
                        'submitLabel' => 'Simpan Siswa',
                        'student' => null,
                    ])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
