<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\FonnteAccount;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FonnteService
{
    public function sendParentGroupCheckInMessage(string $studentName, Carbon $checkInAt, string $attendanceStatus): void
    {
        $message = implode("\n", [
            '*Notifikasi Absensi Masuk*',
            'Nama: ' . $studentName,
            'Pukul masuk: ' . $checkInAt->copy()->timezone('Asia/Jakarta')->format('H:i') . ' WIB',
            'Status: ' . $this->translateAttendanceStatus($attendanceStatus),
        ]);

        $this->sendParentGroupMessage(FonnteAccount::EVENT_CHECK_IN, $message);
    }

    public function sendParentGroupCheckOutMessage(string $studentName, Carbon $checkOutAt, string $attendanceStatus): void
    {
        $message = implode("\n", [
            '*Notifikasi Absensi Pulang*',
            'Nama: ' . $studentName,
            'Pukul pulang: ' . $checkOutAt->copy()->timezone('Asia/Jakarta')->format('H:i') . ' WIB',
            'Status: ' . $this->translateAttendanceStatus($attendanceStatus),
        ]);

        $this->sendParentGroupMessage(FonnteAccount::EVENT_CHECK_OUT, $message);
    }

    private function sendParentGroupMessage(string $eventType, string $message): void
    {
        $account = FonnteAccount::activeForEvent($eventType);

        if ($account === null) {
            return;
        }

        $token = trim((string) $account->token);
        $target = trim((string) $account->parent_group_target);

        if ($token === '' || $target === '') {
            return;
        }

        $baseUrl = rtrim((string) ($account->base_url ?: 'https://api.fonnte.com'), '/');
        $timeout = max(1, (int) ($account->timeout ?: 10));

        try {
            /** @var Response $response */
            $response = Http::asForm()
                ->acceptJson()
                ->withHeaders([
                    'Authorization' => $token,
                ])
                ->baseUrl($baseUrl)
                ->timeout($timeout)
                ->post('/send', [
                    'target' => $target,
                    'message' => $message,
                ]);

            if ($response->failed()) {
                Log::warning('Fonnte attendance notification request failed.', [
                    'event_type' => $eventType,
                    'account_id' => $account->id,
                    'http_status' => $response->status(),
                    'response' => $response->json(),
                ]);
            }
        } catch (Throwable $exception) {
            Log::warning('Fonnte attendance notification failed.', [
                'event_type' => $eventType,
                'account_id' => $account->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function translateAttendanceStatus(string $status): string
    {
        return match ($status) {
            Attendance::STATUS_ARRIVED => 'Hadir Tepat Waktu',
            Attendance::STATUS_LATE => 'Terlambat',
            Attendance::STATUS_DEPARTED => 'Pulang',
            Attendance::STATUS_EARLY_LEAVE => 'Pulang Cepat',
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }
}
