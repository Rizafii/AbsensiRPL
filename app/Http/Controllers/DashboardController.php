<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Setting;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $setting = Setting::current();

        $totalStudents = Student::query()->count();
        $presentToday = Attendance::query()
            ->whereDate('date', $today)
            ->count();

        $lateThreshold = Carbon::parse($today.' '.$setting->check_in_time, 'Asia/Jakarta')
            ->addMinutes($setting->late_tolerance);

        $lateToday = Attendance::query()
            ->whereDate('date', $today)
            ->whereNotNull('check_in')
            ->where('check_in', '>', $lateThreshold)
            ->count();

        $notAttended = max($totalStudents - $presentToday, 0);

        return view('dashboard', [
            'totalStudents' => $totalStudents,
            'presentToday' => $presentToday,
            'lateToday' => $lateToday,
            'notAttended' => $notAttended,
            'refreshedAt' => Carbon::now('Asia/Jakarta')->format('H:i:s'),
        ]);
    }
}
