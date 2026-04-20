<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\EnrollController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.token')->group(function (): void {
    Route::post('/attendance', [AttendanceController::class, 'store']);

    Route::post('/enroll', [EnrollController::class, 'store']);
    Route::get('/enroll/latest', [EnrollController::class, 'latest']);
    Route::post('/enroll/done', [EnrollController::class, 'done']);

    Route::get('/users/{id}', [UserController::class, 'show']);
});
