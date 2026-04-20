<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard Sistem Absensi IoT Fingerprint
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="mb-5 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                Data akan diperbarui otomatis setiap 5 detik. Refresh terakhir: {{ $refreshedAt }} WIB
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <p class="text-sm text-gray-500">Total Siswa</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $totalStudents }}</p>
                </div>

                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <p class="text-sm text-gray-500">Hadir Hari Ini</p>
                    <p class="mt-2 text-3xl font-bold text-green-600">{{ $presentToday }}</p>
                </div>

                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <p class="text-sm text-gray-500">Telat</p>
                    <p class="mt-2 text-3xl font-bold text-amber-600">{{ $lateToday }}</p>
                </div>

                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <p class="text-sm text-gray-500">Belum Absen</p>
                    <p class="mt-2 text-3xl font-bold text-rose-600">{{ $notAttended }}</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        setTimeout(function () {
            window.location.reload();
        }, 5000);
    </script>
</x-app-layout>
