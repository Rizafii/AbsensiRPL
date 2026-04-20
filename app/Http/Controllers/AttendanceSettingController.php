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

        $schoolDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        $payload = [
            'late_tolerance' => (int) $validated['late_tolerance'],
            'early_leave_tolerance' => (int) $validated['early_leave_tolerance'],
        ];

        foreach ($schoolDays as $day) {
            $payload[$day.'_check_in_time'] = $this->toDatabaseTime($validated[$day.'_check_in_time']);
            $payload[$day.'_check_out_time'] = $this->toDatabaseTime($validated[$day.'_check_out_time']);
        }

        $payload['check_in_time'] = $payload['monday_check_in_time'];
        $payload['check_out_time'] = $payload['monday_check_out_time'];

        $setting->update($payload);

        return redirect()
            ->route('settings.attendance.edit')
            ->with('status', 'Pengaturan absensi berhasil diperbarui.');
    }

    private function toDatabaseTime(string $time): string
    {
        return Carbon::createFromFormat('H:i', $time, 'Asia/Jakarta')->format('H:i:s');
    }
}
