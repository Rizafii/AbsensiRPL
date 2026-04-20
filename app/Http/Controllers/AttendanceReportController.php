<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceReportController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $selectedDate = $validated['date'] ?? Carbon::now('Asia/Jakarta')->toDateString();

        $attendances = Attendance::query()
            ->with('student:id,name')
            ->whereDate('date', $selectedDate)
            ->orderBy('check_in')
            ->get();

        return view('reports.attendance', [
            'selectedDate' => $selectedDate,
            'attendances' => $attendances,
        ]);
    }
}
