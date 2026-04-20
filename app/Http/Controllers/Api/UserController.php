<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function show(int $id): JsonResponse
    {
        $student = Student::query()
            ->where('fingerprint_id', $id)
            ->first();

        if ($student === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'users' => [
                'name' => $student->name,
                'category_name' => 'Siswa',
                'id_fingerprint' => $student->fingerprint_id,
            ],
        ]);
    }
}
