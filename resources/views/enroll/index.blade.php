<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800 leading-tight">
            Enroll Sidik Jari
        </h2>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Enroll Form --}}
        <div class="lg:col-span-1">
            <x-bladewind::card title="Pindai Baru" shadow="true">
                <p class="text-sm text-slate-500 mb-6">
                    Masukkan Fingerprint ID dan klik tombol di bawah untuk memulai proses pemindaian pada alat ESP32.
                </p>

                <form id="enroll-form" class="space-y-4">
                    <x-bladewind::input
                        name="fingerprint_id"
                        label="Fingerprint ID"
                        placeholder="Contoh: 1"
                        type="number"
                        numeric="true"
                        id="fingerprint_id"
                        value="{{ request('fingerprint_id') }}"
                        required="true"
                        prefix-icon="identification"
                    />

                    <x-bladewind::button
                        can_submit="true"
                        class="w-full"
                        icon="finger-print"
                    >
                        Pindai Sidik Jari
                    </x-bladewind::button>
                </form>

                <div id="enroll-message" class="mt-4 hidden rounded-xl px-4 py-3 text-sm font-medium transition-all duration-300"></div>
            </x-bladewind::card>

            <div class="mt-6">
                <x-bladewind::alert
                    type="info"
                    show_close_icon="false"
                    show_icon="true"
                >
                    Pastikan ESP32 dalam keadaan Online sebelum menekan tombol pindai.
                </x-bladewind::alert>
            </div>
        </div>

        {{-- History Table --}}
        <div class="lg:col-span-2">
            <x-bladewind::card title="Riwayat Enroll Terbaru" shadow="true">
                <x-bladewind::table striped="true" divider="thin" hover="true">
                    <x-slot name="header">
                        <th>ID Jari</th>
                        <th>Status</th>
                        <th>Waktu Request</th>
                    </x-slot>

                    @forelse ($enrollRequests as $enrollRequest)
                        <tr class="hover:bg-slate-50">
                            <td class="font-semibold text-slate-700">#{{ $enrollRequest->fingerprint_id }}</td>
                            <td>
                                <x-bladewind::tag
                                    label="{{ strtoupper($enrollRequest->status) }}"
                                    color="{{ $enrollRequest->status === 'pending' ? 'orange' : 'green' }}"
                                    shade="faint"
                                />
                            </td>
                            <td class="text-slate-500 text-sm">
                                {{ $enrollRequest->created_at?->timezone('Asia/Jakarta')->format('d M Y, H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-slate-400 py-12">
                                <div class="flex flex-col items-center">
                                    <x-bladewind::icon name="document-magnifying-glass" class="h-10 w-10 mb-2 opacity-20" />
                                    <span>Belum ada permintaan enroll.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </x-bladewind::table>
            </x-bladewind::card>
        </div>
    </div>

    <script>
        const enrollForm = document.getElementById('enroll-form');
        const enrollMessage = document.getElementById('enroll-message');

        enrollForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            const fingerprintId = Number(document.getElementById('fingerprint_id').value);

            enrollMessage.className = 'mt-4 rounded-lg px-4 py-3 text-sm';
            enrollMessage.textContent = 'Mengirim permintaan enroll...';
            enrollMessage.classList.add('bg-blue-50', 'text-blue-700');
            enrollMessage.classList.remove('hidden');

            try {
                const response = await fetch('/api/enroll', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer jgk0advefk90gj4ngin4290',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        fingerprint_id: fingerprintId,
                    }),
                });

                const payload = await response.json();

                if (!response.ok || payload.status === 'error') {
                    enrollMessage.className = 'mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700';
                    enrollMessage.textContent = payload.message ?? 'Gagal memulai enroll.';
                    return;
                }

                enrollMessage.className = 'mt-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700';
                enrollMessage.textContent = payload.message;

                setTimeout(function () {
                    window.location.reload();
                }, 800);
            } catch (error) {
                enrollMessage.className = 'mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700';
                enrollMessage.textContent = 'Terjadi kesalahan jaringan saat mengirim permintaan enroll.';
            }
        });
    </script>
</x-app-layout>
