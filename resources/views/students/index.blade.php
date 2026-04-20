<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Manajemen Siswa
            </h2>

            <a
                href="{{ route('students.create') }}"
                class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gray-700"
            >
                Tambah Siswa
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Nama</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">NIS</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Fingerprint ID</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($students as $student)
                                <tr>
                                    <td class="px-4 py-3 text-gray-900">{{ $student->name }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $student->nis }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $student->fingerprint_id }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a
                                                href="{{ route('students.edit', $student) }}"
                                                class="rounded border border-gray-300 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-100"
                                            >
                                                Edit
                                            </a>

                                            <a
                                                href="{{ route('enroll.index', ['fingerprint_id' => $student->fingerprint_id]) }}"
                                                class="rounded border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-blue-700 hover:bg-blue-100"
                                            >
                                                Pindai Sidik Jari
                                            </a>

                                            <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('Hapus siswa ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="rounded border border-red-200 bg-red-50 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-red-700 hover:bg-red-100"
                                                >
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                        Belum ada data siswa.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 px-4 py-3">
                    {{ $students->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
