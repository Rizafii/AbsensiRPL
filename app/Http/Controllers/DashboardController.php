<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBackupAttendanceRequest;
use App\Models\Attendance;
use App\Models\Setting;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();
        $setting = Setting::current();

        $totalStudents = Student::query()->count();
        $presentToday = Attendance::query()
            ->whereDate('date', $today)
            ->count();

        $lateToday = 0;

        if ($setting->isSchoolDay($now)) {
            $checkInTime = $setting->checkInTimeFor($now);

            if ($checkInTime !== null) {
                $lateThreshold = Carbon::parse($today.' '.$checkInTime, 'Asia/Jakarta')
                    ->addMinutes($setting->late_tolerance);

                $lateToday = Attendance::query()
                    ->whereDate('date', $today)
                    ->whereNotNull('check_in')
                    ->where('check_in', '>', $lateThreshold)
                    ->count();
            }
        }

        $notAttended = max($totalStudents - $presentToday, 0);

        return view('dashboard', [
            'totalStudents' => $totalStudents,
            'presentToday' => $presentToday,
            'lateToday' => $lateToday,
            'notAttended' => $notAttended,
            'refreshedAt' => $now->format('H:i:s'),
            'setting' => $setting,
        ]);
    }

    public function updateBackupAttendance(UpdateBackupAttendanceRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $setting = Setting::current();

        $setting->update([
            'backup_attendance_enabled' => $request->boolean('backup_attendance_enabled'),
            'backup_attendance_radius_meters' => (int) $validated['backup_attendance_radius_meters'],
            'school_latitude' => isset($validated['school_latitude']) ? (float) $validated['school_latitude'] : null,
            'school_longitude' => isset($validated['school_longitude']) ? (float) $validated['school_longitude'] : null,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Pengaturan absensi cadangan berhasil diperbarui.');
    }
}
