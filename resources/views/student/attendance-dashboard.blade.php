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
                    {{ $setting->backup_attendance_enabled ? 'Absensi Aktif' : 'Absensi Nonaktif' }}
                </span>
            </div>
        </x-bladewind::card>

        @if ($setting->backup_attendance_enabled)
            <x-bladewind::card class="!p-6 border-slate-200 shadow-sm">
                @php
                    $isTodayAttendanceCompleted = $todayAttendance?->check_in !== null && $todayAttendance?->check_out !== null;
                @endphp

                <h3 class="text-lg font-semibold text-slate-800">Absensi</h3>
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
                                    Sebelum Absensi, lakukan pendaftaran wajah satu kali menggunakan kamera perangkat Anda.
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
                    @endif

                    @if (! $isTodayAttendanceCompleted)
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

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <x-bladewind::button id="start_backup_attendance_button" color="blue" icon="check"
                                    uppercasing="false">
                                    Absensi Sekarang
                                </x-bladewind::button>
                            </div>

                            <x-input-error :messages="$errors->get('latitude')" class="mt-2" />
                            <x-input-error :messages="$errors->get('longitude')" class="mt-2" />
                            <x-input-error :messages="$errors->get('face_descriptor')" class="mt-2" />
                        </form>
                    @else
                        <x-bladewind::alert type="success" show_close_icon="false" class="!mb-0">
                            Absensi hari ini sudah lengkap.
                        </x-bladewind::alert>
                    @endif

                    <x-bladewind::modal name="attendance-face-recognition-modal" title="Verifikasi Wajah"
                        show_action_buttons="false" show_close_icon="false" backdrop_can_close="false" size="medium">
                        <div class="space-y-4">
                            <x-bladewind::alert type="info" show_close_icon="false" class="!mb-0">
                                <p id="modal_face_hint" class="text-sm">
                                    Posisikan wajah di dalam frame. Verifikasi akan berjalan otomatis (Berkedip untuk Liveness).
                                </p>
                            </x-bladewind::alert>

                            <div class="relative overflow-hidden rounded-xl border border-slate-200 bg-slate-950">
                                <video id="face_camera_preview_modal" class="h-72 w-full object-cover scale-x-[-1]" autoplay muted playsinline></video>
                                <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                                    <div class="h-56 w-40 rounded-[999px] border-2 border-dashed border-white/90"></div>
                                </div>
                                <div class="absolute inset-x-0 bottom-0 bg-black/60 px-3 py-2 text-center text-xs text-white">
                                    Pastikan wajah berada di tengah frame sebagai acuan.
                                </div>
                            </div>

                            <p id="modal_face_status_message" class="text-sm text-slate-600">
                                Kamera belum dimulai.
                            </p>

                            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                                <x-bladewind::button id="close_face_recognition_button" type="secondary" outline="true"
                                    uppercasing="false">
                                    Batal
                                </x-bladewind::button>

                                <x-bladewind::button id="confirm_face_recognition_button" color="blue"
                                    uppercasing="false" disabled="true" class="hidden">
                                    Verifikasi Wajah
                                </x-bladewind::button>
                            </div>
                        </div>
                    </x-bladewind::modal>
                </div>
            </x-bladewind::card>
        @else
            <x-bladewind::card class="!p-6 border-amber-200 bg-amber-50/60">
                <h3 class="text-lg font-semibold text-amber-800">Fitur Belum Diaktifkan Guru</h3>
                <p class="mt-1 text-sm text-amber-700">
                    Absensi hanya dapat digunakan ketika guru mengaktifkannya dari dashboard admin.
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
