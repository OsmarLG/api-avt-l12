<?php

use App\Http\Controllers\Api\ZoneController;
use Illuminate\Support\Facades\Route;

Route::prefix('zones')->group(function () {
    Route::get('/select', [ZoneController::class, 'select']);
    Route::get('/', [ZoneController::class, 'index']);
    Route::post('/', [ZoneController::class, 'store']);
    Route::get('/{zone}', [ZoneController::class, 'show']);
    Route::put('/{zone}', [ZoneController::class, 'update']);
    Route::delete('/{zone}', [ZoneController::class, 'destroy']);
});
