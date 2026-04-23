<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Notifications\AttendanceSavedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

class AttendanceService
{
    public function __construct(
        private readonly FonnteService $fonnteService,
        private readonly FaceRecognitionService $faceRecognitionService,
    ) {}

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

        return $this->processForStudent($student);
    }

    /**
     * @return array{status: string, message: string, student_name?: string, attendance_status?: string}
     */
    public function processBackupByStudent(
        Student $student,
        float $latitude,
        float $longitude,
        string $faceDescriptor,
    ): array {
        $setting = Setting::current();

        if (! $setting->backup_attendance_enabled) {
            return [
                'status' => 'error',
                'message' => 'Absensi cadangan belum diaktifkan oleh guru.',
            ];
        }

        if ($setting->school_latitude === null || $setting->school_longitude === null) {
            return [
                'status' => 'error',
                'message' => 'Koordinat sekolah belum diatur oleh guru.',
            ];
        }

        if ($student->face_descriptor === null) {
            return [
                'status' => 'error',
                'message' => 'Template wajah Anda belum terdaftar. Silakan daftar wajah terlebih dahulu.',
            ];
        }

        $faceVerification = $this->faceRecognitionService->verifyDescriptor(
            $faceDescriptor,
            $student->face_descriptor,
        );

        if (! $faceVerification['verified']) {
            return [
                'status' => 'error',
                'message' => $faceVerification['message'] ?? 'Verifikasi wajah gagal. Silakan coba lagi.',
            ];
        }

        $distanceMeter = $this->calculateDistanceInMeters(
            $latitude,
            $longitude,
            (float) $setting->school_latitude,
            (float) $setting->school_longitude,
        );

        if ($distanceMeter > (float) $setting->backup_attendance_radius_meters) {
            return [
                'status' => 'error',
                'message' => sprintf('Anda berada di luar radius absensi yang diizinkan (jarak %.2f meter).', $distanceMeter),
            ];
        }

        return $this->processForStudent($student);
    }

    /**
     * @return array{status: string, message: string, student_name?: string, attendance_status?: string}
     */
    private function processForStudent(Student $student): array
    {
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();
        $setting = Setting::current();

        if (! $setting->isSchoolDay($now)) {
            return [
                'status' => 'error',
                'message' => 'Absensi hanya tersedia pada hari Senin sampai Jumat.',
            ];
        }

        /** @var array{student_name: string, check_in_at: Carbon, status: string}|null $checkInNotification */
        $checkInNotification = null;
        /** @var array{student_name: string, check_out_at: Carbon, status: string}|null $checkOutNotification */
        $checkOutNotification = null;

        $result = DB::transaction(function () use ($student, $now, $today, $setting, &$checkInNotification, &$checkOutNotification): array {
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

                $checkInNotification = [
                    'student_name' => $student->name,
                    'check_in_at' => $now->copy(),
                    'status' => $status,
                ];

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

                $checkOutNotification = [
                    'student_name' => $student->name,
                    'check_out_at' => $now->copy(),
                    'status' => $status,
                ];

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

        if ($checkInNotification !== null) {
            $this->fonnteService->sendParentGroupCheckInMessage(
                $checkInNotification['student_name'],
                $checkInNotification['check_in_at'],
                $checkInNotification['status'],
            );

            $this->notifyAdminsAboutAttendance(
                $checkInNotification['student_name'],
                'check-in',
                $checkInNotification['status'],
                $checkInNotification['check_in_at'],
            );
        }

        if ($checkOutNotification !== null) {
            $this->fonnteService->sendParentGroupCheckOutMessage(
                $checkOutNotification['student_name'],
                $checkOutNotification['check_out_at'],
                $checkOutNotification['status'],
            );

            $this->notifyAdminsAboutAttendance(
                $checkOutNotification['student_name'],
                'check-out',
                $checkOutNotification['status'],
                $checkOutNotification['check_out_at'],
            );
        }

        return $result;
    }

    private function notifyAdminsAboutAttendance(
        string $studentName,
        string $type,
        string $status,
        Carbon $time,
    ): void {
        try {
            $admins = User::query()
                ->where('role', User::ROLE_ADMIN)
                ->get();

            if ($admins->isEmpty()) {
                return;
            }

            Notification::send($admins, new AttendanceSavedNotification(
                $studentName,
                $type,
                $status,
                $time,
            ));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function resolveArriveStatus(Carbon $now, Setting $setting): string
    {
        $checkInTime = $setting->checkInTimeFor($now);

        if ($checkInTime === null) {
            return Attendance::STATUS_ARRIVED;
        }

        $lateThreshold = Carbon::parse($now->toDateString().' '.$checkInTime, 'Asia/Jakarta')
            ->addMinutes($setting->late_tolerance);

        if ($now->greaterThan($lateThreshold)) {
            return Attendance::STATUS_LATE;
        }

        return Attendance::STATUS_ARRIVED;
    }

    private function resolveDepartureStatus(Carbon $now, Setting $setting): string
    {
        $checkOutTime = $setting->checkOutTimeFor($now);

        if ($checkOutTime === null) {
            return Attendance::STATUS_DEPARTED;
        }

        $earlyLeaveThreshold = Carbon::parse($now->toDateString().' '.$checkOutTime, 'Asia/Jakarta')
            ->subMinutes($setting->early_leave_tolerance);

        if ($now->lessThan($earlyLeaveThreshold)) {
            return Attendance::STATUS_EARLY_LEAVE;
        }

        return Attendance::STATUS_DEPARTED;
    }

    private function calculateDistanceInMeters(
        float $latitudeA,
        float $longitudeA,
        float $latitudeB,
        float $longitudeB,
    ): float {
        $earthRadiusMeter = 6371000;

        $latitudeADegree = deg2rad($latitudeA);
        $latitudeBDegree = deg2rad($latitudeB);
        $latitudeDelta = deg2rad($latitudeB - $latitudeA);
        $longitudeDelta = deg2rad($longitudeB - $longitudeA);

        $haversine = sin($latitudeDelta / 2) ** 2
            + cos($latitudeADegree) * cos($latitudeBDegree) * sin($longitudeDelta / 2) ** 2;

        $centralAngle = 2 * atan2(sqrt($haversine), sqrt(1 - $haversine));

        return $earthRadiusMeter * $centralAngle;
    }
}
