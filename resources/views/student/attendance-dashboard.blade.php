<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800 leading-tight">
            Dashboard Absensi Siswa
        </h2>
    </x-slot>

    @if (session('status'))
        <x-bladewind::alert type="success" class="mb-6">
            {{ session('status') }}
        </x-bladewind::alert>
    @endif

    @if ($errors->has('backup_attendance'))
        <x-bladewind::alert type="error" class="mb-6">
            {{ $errors->first('backup_attendance') }}
        </x-bladewind::alert>
    @endif

    <div class="space-y-6">
        <x-bladewind::card class="!p-6 border-slate-200 shadow-sm">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Halo, {{ $student->name }}</h3>
                    <p class="text-sm text-slate-600">NIS: {{ $student->nis }}</p>
                </div>
                <span
                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $setting->backup_attendance_enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                    {{ $setting->backup_attendance_enabled ? 'Absensi Cadangan Aktif' : 'Absensi Cadangan Nonaktif' }}
                </span>
            </div>
        </x-bladewind::card>

        @if ($setting->backup_attendance_enabled)
            <x-bladewind::card class="!p-6 border-slate-200 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-800">Absensi Cadangan</h3>
                <p class="mt-1 text-sm text-slate-600">
                    Sistem akan mengambil lokasi Anda secara otomatis dan mencocokkan wajah Anda sebelum absensi dikirim.
                    Pastikan Anda berada dalam radius maksimal
                    <span class="font-semibold">{{ $setting->backup_attendance_radius_meters }} meter</span>
                    dari titik sekolah.
                </p>

                <div class="mt-5 space-y-5">
                    @if ($student->face_descriptor === null)
                        <x-bladewind::alert type="warning" show_close_icon="false" class="!mb-0">
                            <div class="space-y-2">
                                <p class="text-sm font-semibold">Template wajah belum terdaftar.</p>
                                <p class="text-sm">
                                    Sebelum absensi cadangan, lakukan pendaftaran wajah satu kali menggunakan kamera perangkat Anda.
                                </p>
                            </div>

                            <form method="POST" action="{{ route('student.attendance.face.store') }}" class="mt-4 space-y-3"
                                data-face-registration-form>
                                @csrf
                                <input type="hidden" name="registration_face_descriptor" id="registration_face_descriptor"
                                    value="{{ old('registration_face_descriptor') }}" />

                                <x-bladewind::button id="capture_registration_face_button" color="yellow"
                                    uppercasing="false">
                                    Daftarkan Template Wajah
                                </x-bladewind::button>

                                <p id="registration_status_message" class="text-sm"></p>
                                <x-input-error :messages="$errors->get('registration_face_descriptor')" class="mt-2" />
                            </form>
                        </x-bladewind::alert>
                    @else
                        <x-bladewind::alert type="success" show_close_icon="false" class="!mb-0">
                            <p class="text-sm font-medium">Template wajah Anda sudah terdaftar.</p>
                        </x-bladewind::alert>
                    @endif

                    <form method="POST" action="{{ route('student.attendance.store') }}" class="space-y-4"
                        data-student-attendance-form
                        data-school-latitude="{{ $setting->school_latitude ?? '' }}"
                        data-school-longitude="{{ $setting->school_longitude ?? '' }}"
                        data-max-radius="{{ $setting->backup_attendance_radius_meters }}"
                        data-enrolled-face-descriptor='@json($student->face_descriptor)'>
                        @csrf

                        <input type="hidden" name="latitude" id="attendance_latitude" value="{{ old('latitude') }}" />
                        <input type="hidden" name="longitude" id="attendance_longitude" value="{{ old('longitude') }}" />
                        <input type="hidden" name="face_descriptor" id="attendance_face_descriptor"
                            value="{{ old('face_descriptor') }}" />

                        @if ($setting->school_latitude === null || $setting->school_longitude === null)
                            <x-bladewind::alert type="error" show_close_icon="false" class="!mb-0">
                                Titik koordinat sekolah belum diatur oleh guru. Hubungi admin untuk melengkapi latitude dan
                                longitude sekolah.
                            </x-bladewind::alert>
                        @endif

                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-bladewind::alert type="info" show_close_icon="false" class="!mb-0">
                                <p class="text-xs uppercase tracking-wider text-slate-400">Status Lokasi</p>
                                <p id="location_status_message" class="mt-2 text-sm font-medium text-slate-700">
                                    Belum diverifikasi.
                                </p>
                                <p id="location_distance_message" class="mt-1 text-xs text-slate-500"></p>
                            </x-bladewind::alert>

                            <x-bladewind::alert type="info" show_close_icon="false" class="!mb-0">
                                <p class="text-xs uppercase tracking-wider text-slate-400">Status Wajah</p>
                                <p id="face_status_message" class="mt-2 text-sm font-medium text-slate-700">
                                    Belum diverifikasi.
                                </p>
                                <p id="face_distance_message" class="mt-1 text-xs text-slate-500"></p>
                            </x-bladewind::alert>
                        </div>

                        <div class="overflow-hidden rounded-lg border border-slate-200 bg-slate-950">
                            <video id="face_camera_preview" class="h-56 w-full object-cover" autoplay muted playsinline></video>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <x-bladewind::button id="prepare_backup_attendance_button" color="blue"
                                uppercasing="false">
                                Ambil Lokasi & Verifikasi Wajah
                            </x-bladewind::button>

                            <x-bladewind::button id="submit_backup_attendance_button" icon="check" can_submit="true"
                                disabled="true" uppercasing="false">
                                Kirim Absensi Cadangan
                            </x-bladewind::button>
                        </div>

                        <x-input-error :messages="$errors->get('latitude')" class="mt-2" />
                        <x-input-error :messages="$errors->get('longitude')" class="mt-2" />
                        <x-input-error :messages="$errors->get('face_descriptor')" class="mt-2" />
                    </form>
                </div>
            </x-bladewind::card>
        @else
            <x-bladewind::card class="!p-6 border-amber-200 bg-amber-50/60">
                <h3 class="text-lg font-semibold text-amber-800">Fitur Belum Diaktifkan Guru</h3>
                <p class="mt-1 text-sm text-amber-700">
                    Absensi cadangan hanya dapat digunakan ketika guru mengaktifkannya dari dashboard admin.
                </p>
            </x-bladewind::card>
        @endif

        <x-bladewind::card class="!p-6 border-slate-200 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-800">Status Absensi Hari Ini</h3>

            @if ($todayAttendance === null)
                <p class="mt-2 text-sm text-slate-600">Belum ada data absensi untuk hari ini.</p>
            @else
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-400">Masuk</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">
                            {{ $todayAttendance->check_in?->timezone('Asia/Jakarta')->format('H:i:s') ?? '-' }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-400">Pulang</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">
                            {{ $todayAttendance->check_out?->timezone('Asia/Jakarta')->format('H:i:s') ?? '-' }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-400">Status</p>
                        <p class="mt-1 text-sm font-semibold text-slate-700">{{ $todayAttendance->statusLabel() }}</p>
                    </div>
                </div>
            @endif
        </x-bladewind::card>

        <x-bladewind::card class="!p-6 border-slate-200 shadow-sm" title="Riwayat 10 Absensi Terakhir">
            <x-bladewind::table striped="true" divider="thin" hover="true">
                <x-slot name="header">
                    <th>Tanggal</th>
                    <th>Masuk</th>
                    <th>Pulang</th>
                    <th>Status</th>
                </x-slot>

                @forelse ($recentAttendances as $attendance)
                    <tr>
                        <td class="font-medium text-slate-700">
                            {{ \Carbon\Carbon::parse($attendance->date)->format('d M Y') }}
                        </td>
                        <td>{{ $attendance->check_in?->timezone('Asia/Jakarta')->format('H:i:s') ?? '-' }}</td>
                        <td>{{ $attendance->check_out?->timezone('Asia/Jakarta')->format('H:i:s') ?? '-' }}</td>
                        <td>
                            <x-bladewind::tag label="{{ $attendance->statusLabel() }}" shade="faint"
                                color="{{ $attendance->statusColor() }}" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-8 text-center text-slate-400">Belum ada riwayat absensi.</td>
                    </tr>
                @endforelse
            </x-bladewind::table>
        </x-bladewind::card>
    </div>
</x-app-layout>
