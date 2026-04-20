<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Setting;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * @return array{status: string, message: string, student_name?: string, attendance_status?: string}
     */
    public function processByFingerprintId(int $fingerprintId): array
    {
        $student = Student::query()
            ->where('fingerprint_id', $fingerprintId)
            ->first();

        if ($student === null) {
            return [
                'status' => 'error',
                'message' => 'Siswa tidak ditemukan.',
            ];
        }

        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();
        $setting = Setting::current();

        return DB::transaction(function () use ($student, $now, $today, $setting): array {
            $attendance = Attendance::query()
                ->where('student_id', $student->id)
                ->whereDate('date', $today)
                ->lockForUpdate()
                ->first();

            if ($attendance === null) {
                $status = $this->resolveArriveStatus($now, $setting);

                Attendance::query()->create([
                    'student_id' => $student->id,
                    'date' => $today,
                    'check_in' => $now,
                    'status' => $status,
                ]);

                return [
                    'status' => 'success',
                    'message' => 'Absensi masuk berhasil disimpan.',
                    'student_name' => $student->name,
                    'attendance_status' => $status,
                ];
            }

            if ($attendance->check_out === null) {
                $status = $this->resolveDepartureStatus($now, $setting);

                $attendance->update([
                    'check_out' => $now,
                    'status' => $status,
                ]);

                return [
                    'status' => 'success',
                    'message' => 'Absensi pulang berhasil disimpan.',
                    'student_name' => $student->name,
                    'attendance_status' => $status,
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Absensi hari ini sudah lengkap.',
            ];
        });
    }

    private function resolveArriveStatus(Carbon $now, Setting $setting): string
    {
        $lateThreshold = Carbon::parse($now->toDateString().' '.$setting->check_in_time, 'Asia/Jakarta')
            ->addMinutes($setting->late_tolerance);

        if ($now->greaterThan($lateThreshold)) {
            return Attendance::STATUS_LATE;
        }

        return Attendance::STATUS_ARRIVED;
    }

    private function resolveDepartureStatus(Carbon $now, Setting $setting): string
    {
        $earlyLeaveThreshold = Carbon::parse($now->toDateString().' '.$setting->check_out_time, 'Asia/Jakarta')
            ->subMinutes($setting->early_leave_tolerance);

        if ($now->lessThan($earlyLeaveThreshold)) {
            return Attendance::STATUS_EARLY_LEAVE;
        }

        return Attendance::STATUS_DEPARTED;
    }
}
