<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800 leading-tight">
            Laporan Absensi
        </h2>
    </x-slot>

    <div class="space-y-6">
        {{-- Filter Section --}}
        <x-bladewind::card title="Filter Laporan" shadow="true">
            <form method="GET" action="{{ route('reports.attendance') }}"
                class="flex flex-col md:flex-row items-center gap-3">
                <div class="w-full md:w-72">
                    <x-bladewind::input name="date" label="Pilih Tanggal" type="date" value="{{ $selectedDate }}"
                        prefix-icon="calendar" margin_bottom="0" />
                </div>

                <div class="w-full md:w-auto">
                    <x-bladewind::button icon="funnel" can_submit="true" type="primary" class="w-full">
                        Tampilkan Laporan
                    </x-bladewind::button>
                </div>
            </form>
        </x-bladewind::card>

        {{-- Table Section --}}
        <x-bladewind::card title="Data Kehadiran: {{ \Carbon\Carbon::parse($selectedDate)->format('d F Y') }}"
            shadow="true">
            <x-bladewind::table striped="true" divider="thin" hover="true">
                <x-slot name="header">
                    <th>Nama Siswa</th>
                    <th>Masuk</th>
                    <th>Pulang</th>
                    <th class="text-center">Status</th>
                </x-slot>

                @forelse ($attendances as $attendance)
                                <tr class="hover:bg-slate-50">
                                    <td class="font-medium text-slate-700">{{ $attendance->student?->name }}</td>
                                    <td class="text-slate-600">
                                        <span class="flex items-center gap-1">
                                            <x-bladewind::icon name="clock" class="h-4 w-4 text-slate-400" />
                                            {{ $attendance->check_in?->timezone('Asia/Jakarta')->format('H:i:s') ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="text-slate-600">
                                        <span class="flex items-center gap-1">
                                            <x-bladewind::icon name="clock" class="h-4 w-4 text-slate-400" />
                                            {{ $attendance->check_out?->timezone('Asia/Jakarta')->format('H:i:s') ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <x-bladewind::tag label="{{ strtoupper(str_replace('_', ' ', $attendance->status)) }}"
                                            shade="faint" color="{{ match ($attendance->status) {
                        'on_time' => 'green',
                        'late' => 'orange',
                        'absent' => 'red',
                        default => 'gray',
                    } }}" />
                                    </td>
                                </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-slate-400 py-16">
                            <div class="flex flex-col items-center">
                                <x-bladewind::icon name="no-symbol" class="h-12 w-12 mb-3 opacity-20" />
                                <span class="text-lg font-medium">Tidak ada data absensi</span>
                                <span class="text-sm">Silakan pilih tanggal lain atau cek koneksi alat.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-bladewind::table>
        </x-bladewind::card>
    </div>
</x-app-layout>