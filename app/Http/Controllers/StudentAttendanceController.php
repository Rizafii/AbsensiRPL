<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBackupAttendanceRequest;
use App\Http\Requests\StoreFaceDescriptorRequest;
use App\Models\Attendance;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\FaceRecognitionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentAttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
        private readonly FaceRecognitionService $faceRecognitionService,
    ) {}

    public function index(Request $request): View
    {
        $student = $this->resolveStudent($request->user());
        $setting = Setting::current();
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $todayAttendance = Attendance::query()
            ->where('student_id', $student->id)
            ->whereDate('date', $today)
            ->first();

        $recentAttendances = Attendance::query()
            ->where('student_id', $student->id)
            ->orderByDesc('date')
            ->orderByDesc('check_in')
            ->limit(10)
            ->get();

        return view('student.attendance-dashboard', [
            'student' => $student,
            'setting' => $setting,
            'todayAttendance' => $todayAttendance,
            'recentAttendances' => $recentAttendances,
        ]);
    }

    public function store(StoreBackupAttendanceRequest $request): RedirectResponse
    {
        $student = $this->resolveStudent($request->user());
        $validated = $request->validated();

        $result = $this->attendanceService->processBackupByStudent(
            $student,
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            (string) $validated['face_descriptor'],
        );

        if ($result['status'] === 'error') {
            return back()->withErrors([
                'backup_attendance' => $result['message'],
            ]);
        }

        return back()->with('status', $result['message']);
    }

    public function storeFaceDescriptor(StoreFaceDescriptorRequest $request): RedirectResponse
    {
        $student = $this->resolveStudent($request->user());
        $validated = $request->validated();

        $descriptor = $this->faceRecognitionService->normalizeDescriptorFromJson(
            (string) $validated['registration_face_descriptor'],
        );

        if ($descriptor === null) {
            return back()->withErrors([
                'registration_face_descriptor' => 'Template wajah tidak valid. Silakan ulangi pemindaian wajah.',
            ]);
        }

        $student->update([
            'face_descriptor' => $descriptor,
        ]);

        return back()->with('status', 'Template wajah berhasil disimpan.');
    }

    private function resolveStudent(?User $user): Student
    {
        $student = $user?->student;

        abort_if($student === null, 403);

        return $student;
    }
}
