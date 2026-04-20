<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAttendanceSettingRequest;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AttendanceSettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.attendance', [
            'setting' => Setting::current(),
        ]);
    }

    public function update(UpdateAttendanceSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $setting = Setting::current();

        $setting->update([
            'check_in_time' => Carbon::createFromFormat('H:i', $validated['check_in_time'], 'Asia/Jakarta')->format('H:i:s'),
            'check_out_time' => Carbon::createFromFormat('H:i', $validated['check_out_time'], 'Asia/Jakarta')->format('H:i:s'),
            'late_tolerance' => (int) $validated['late_tolerance'],
            'early_leave_tolerance' => (int) $validated['early_leave_tolerance'],
        ]);

        return redirect()
            ->route('settings.attendance.edit')
            ->with('status', 'Pengaturan absensi berhasil diperbarui.');
    }
}
