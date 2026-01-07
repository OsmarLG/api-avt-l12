<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;

Route::prefix('users')->group(function () {
    // Selects / combos
    Route::get('/select', [UserController::class, 'select']);   // para dropdowns (id, label)
    Route::get('/options', [UserController::class, 'options']); // opcional, alias

    // CRUD
    Route::get('/', [UserController::class, 'index']);
    Route::get('/{user}', [UserController::class, 'show']); // route model binding
    Route::post('/', [UserController::class, 'store']);
    Route::put('/{user}', [UserController::class, 'update']);
    Route::delete('/{user}', [UserController::class, 'destroy']);
});
