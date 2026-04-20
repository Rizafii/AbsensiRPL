<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiEnrollDoneRequest;
use App\Http\Requests\ApiEnrollStartRequest;
use App\Services\EnrollService;
use Illuminate\Http\JsonResponse;

class EnrollController extends Controller
{
    public function __construct(private readonly EnrollService $enrollService)
    {
    }

    public function store(ApiEnrollStartRequest $request): JsonResponse
    {
        $enrollRequest = $this->enrollService->start((int) $request->validated('fingerprint_id'));

        return response()->json([
            'status' => 'success',
            'message' => 'Enroll started',
            'fingerprint_id' => $enrollRequest->fingerprint_id,
        ]);
    }

    public function latest(): JsonResponse
    {
        $pending = $this->enrollService->latestPending();

        if ($pending === null) {
            return response()->json([
                'status' => 'empty',
            ]);
        }

        return response()->json([
            'status' => 'pending',
            'fingerprint_id' => $pending->fingerprint_id,
        ]);
    }

    public function done(ApiEnrollDoneRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $enrollRequest = $this->enrollService->complete((int) $validated['fingerprint_id']);

        if ($enrollRequest === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Enroll request not found',
            ], 404);
        }

        $message = $validated['status'] === 'success'
            ? 'Enroll success recorded'
            : 'Enroll failed recorded';

        return response()->json([
            'status' => 'success',
            'message' => $message,
        ]);
    }
}
