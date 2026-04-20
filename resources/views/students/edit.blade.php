<x-app-layout>
    <div class="max-w-3xl space-y-6">
        {{-- Header --}}
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 border border-blue-100">
                <x-bladewind::icon name="pencil-square" class="h-5 w-5" />
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Edit Siswa</h1>
                <p class="text-sm text-slate-500">Perbarui data informasi siswa {{ $student->name }}.</p>
            </div>
        </div>

        <x-bladewind::card class="!rounded-2xl border-slate-100 shadow-sm">
            <form method="POST" action="{{ route('students.update', $student) }}">
                @csrf
                @method('PUT')

                @include('students.partials.form', [
                    'submitLabel' => 'Perbarui Siswa',
                    'student' => $student,
                ])
            </form>
        </x-bladewind::card>
    </div>
</x-app-layout>
