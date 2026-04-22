<?php

use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\AttendanceSettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollController;
use App\Http\Controllers\FonnteSettingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::put('/dashboard/backup-attendance', [DashboardController::class, 'updateBackupAttendance'])
        ->name('dashboard.backup-attendance.update');

    Route::resource('students', StudentController::class)->except('show');

    Route::get('/settings/attendance', [AttendanceSettingController::class, 'edit'])
        ->name('settings.attendance.edit');
    Route::put('/settings/attendance', [AttendanceSettingController::class, 'update'])
        ->name('settings.attendance.update');

    Route::get('/settings/fonnte', [FonnteSettingController::class, 'edit'])
        ->name('settings.fonnte.edit');
    Route::put('/settings/fonnte', [FonnteSettingController::class, 'update'])
        ->name('settings.fonnte.update');

    Route::get('/reports/attendance', [AttendanceReportController::class, 'index'])
        ->name('reports.attendance');

    Route::get('/enroll', EnrollController::class)->name('enroll.index');
});

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/dashboard/absensi', [StudentAttendanceController::class, 'index'])
        ->name('student.attendance.dashboard');
    Route::post('/dashboard/absensi/wajah', [StudentAttendanceController::class, 'storeFaceDescriptor'])
        ->name('student.attendance.face.store');
    Route::post('/dashboard/absensi', [StudentAttendanceController::class, 'store'])
        ->name('student.attendance.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
