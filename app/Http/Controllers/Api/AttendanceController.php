<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiAttendanceRequest;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService)
    {
    }

    public function store(ApiAttendanceRequest $request): JsonResponse
    {
        $result = $this->attendanceService->processByFingerprintId((int) $request->validated('user_id'));

        if ($result['status'] === 'error') {
            return response()->json([
                'status' => 'error',
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => $result['message'],
            'user' => [
                'name' => $result['student_name'],
            ],
            'data' => [
                'status' => $result['attendance_status'],
            ],
        ]);
    }
}
