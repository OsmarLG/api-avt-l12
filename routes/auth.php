<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});
