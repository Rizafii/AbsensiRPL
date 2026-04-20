<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Siswa
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <form method="POST" action="{{ route('students.update', $student) }}">
                    @method('PUT')

                    @include('students.partials.form', [
                        'submitLabel' => 'Perbarui Siswa',
                        'student' => $student,
                    ])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
