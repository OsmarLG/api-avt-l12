<?php

use App\Http\Controllers\Api\PredioController;
use App\Http\Controllers\Api\PredioObservacionController;
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
    Route::get('/withoutPagination', [PredioController::class, 'indexWithoutPagination']);
    Route::get('/{predio}', [PredioController::class, 'show']);
    Route::post('/', [PredioController::class, 'store']);
    Route::put('/{predio}', [PredioController::class, 'update']);
    Route::delete('/{predio}', [PredioController::class, 'destroy']);

    // Observaciones
    Route::get('/{predio}/observaciones', [PredioObservacionController::class, 'index'])->name('predios.observaciones.index');
    Route::post('/{predio}/observaciones', [PredioObservacionController::class, 'store'])->name('predios.observaciones.store');
    Route::get('/{predio}/observaciones/{observacion}', [PredioObservacionController::class, 'show'])->name('predios.observaciones.show');
    Route::put('/{predio}/observaciones/{observacion}', [PredioObservacionController::class, 'update'])->name('predios.observaciones.update');
    Route::delete('/{predio}/observaciones/{observacion}', [PredioObservacionController::class, 'destroy'])->name('predios.observaciones.destroy');
});
