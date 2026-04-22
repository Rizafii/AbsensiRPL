<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceSavedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $studentName,
        public string $type, // 'check-in' or 'check-out'
        public string $status,
        public Carbon $time,
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'student_name' => $this->studentName,
            'type' => $this->type,
            'status' => $this->status,
            'time' => $this->time->toIso8601String(),
            'message' => "{$this->studentName} " . ($this->type === 'check-in' ? 'melakukan absensi masuk' : 'melakukan absensi pulang') . " ({$this->status})",
        ];
    }
}
