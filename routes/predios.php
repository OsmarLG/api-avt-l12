<?php

use App\Http\Controllers\Api\PredioController;
use Illuminate\Support\Facades\Route;

Route::prefix('predios')->group(function () {
    // Import
    Route::post('/import', [PredioController::class, 'import']);

    // Selects / combos
    Route::get('/select', [PredioController::class, 'select']);
    Route::get('/options', [PredioController::class, 'options']);

    // Distance search
    Route::get('/distance', [PredioController::class, 'byDistance']);

    // CRUD
    Route::get('/', [PredioController::class, 'index']);
    Route::get('/{predio}', [PredioController::class, 'show']);
    Route::post('/', [PredioController::class, 'store']);
    Route::put('/{predio}', [PredioController::class, 'update']);
    Route::delete('/{predio}', [PredioController::class, 'destroy']);
});
