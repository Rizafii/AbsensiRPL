<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Laporan Absensi
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                <form method="GET" action="{{ route('reports.attendance') }}" class="flex flex-wrap items-end gap-3">
                    <div>
                        <x-input-label for="date" :value="__('Tanggal')" />
                        <x-text-input id="date" name="date" type="date" class="mt-1 block" :value="$selectedDate" />
                    </div>

                    <x-primary-button>
                        Filter
                    </x-primary-button>
                </form>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Nama</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Jam Masuk</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Jam Pulang</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($attendances as $attendance)
                                <tr>
                                    <td class="px-4 py-3 text-gray-900">{{ $attendance->student?->name }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $attendance->check_in?->timezone('Asia/Jakarta')->format('H:i:s') ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $attendance->check_out?->timezone('Asia/Jakarta')->format('H:i:s') ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-gray-700">
                                            {{ str_replace('_', ' ', $attendance->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                        Tidak ada data absensi pada tanggal ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
