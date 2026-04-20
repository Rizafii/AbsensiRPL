<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Enroll Sidik Jari
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <p class="mb-4 text-sm text-gray-600">
                    Klik tombol Pindai Sidik Jari untuk mengirim perintah enroll ke ESP32 melalui endpoint API.
                </p>

                <form id="enroll-form" class="flex flex-wrap items-end gap-3">
                    <div>
                        <x-input-label for="fingerprint_id" :value="__('Fingerprint ID')" />
                        <x-text-input
                            id="fingerprint_id"
                            name="fingerprint_id"
                            type="number"
                            min="1"
                            class="mt-1 block"
                            value="{{ request('fingerprint_id') }}"
                            required
                        />
                    </div>

                    <button
                        type="submit"
                        class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-blue-700"
                    >
                        Pindai Sidik Jari
                    </button>
                </form>

                <div id="enroll-message" class="mt-4 hidden rounded-lg px-4 py-3 text-sm"></div>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h3 class="font-semibold text-gray-800">Riwayat Enroll Terbaru</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Fingerprint ID</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($enrollRequests as $enrollRequest)
                                <tr>
                                    <td class="px-4 py-3 text-gray-900">{{ $enrollRequest->fingerprint_id }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wider {{ $enrollRequest->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' }}">
                                            {{ $enrollRequest->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ $enrollRequest->created_at?->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center text-gray-500">
                                        Belum ada permintaan enroll.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
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
