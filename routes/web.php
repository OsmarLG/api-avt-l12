<?php

use App\Http\Controllers\ImporterAuthController;
use App\Http\Controllers\PadronImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'App' => config('app.name'),
        'PHP Version' => PHP_VERSION,
        'Laravel Version' => app()->version(),
        'Environment' => config('app.env'),
        'message' => 'API is running',
    ]);
});

/*
|--------------------------------------------------------------------------
| Importador de padrones
|--------------------------------------------------------------------------
|
| Únicas vistas de la API. Sirven para cargar padrones (Excel + GeoJSON del
| catastro), revisar la simulación y aplicarla o revertirla. Usan sesión web
| con los mismos usuarios de la API.
|
*/

Route::get('/importador/login', [ImporterAuthController::class, 'show'])->name('imports.login');
Route::post('/importador/login', [ImporterAuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/importador/logout', [ImporterAuthController::class, 'logout'])->name('imports.logout');

Route::middleware('auth')->prefix('importador')->name('imports.')->group(function () {
    Route::get('/', [PadronImportController::class, 'index'])->name('padron.index');
    Route::get('/padron', [PadronImportController::class, 'create'])->name('padron.create');
    Route::post('/padron/previsualizar', [PadronImportController::class, 'preview'])->name('padron.preview');

    Route::prefix('padron/{lote:uuid}')->name('padron.')->group(function () {
        Route::get('/', [PadronImportController::class, 'show'])->name('show');
        Route::get('/analisis.csv', [PadronImportController::class, 'reporteAnalisis'])->name('analisis');
        Route::get('/registros.csv', [PadronImportController::class, 'registros'])->name('registros');
        Route::get('/registro/{registro}', [PadronImportController::class, 'registro'])->name('registro');
        Route::post('/aplicar', [PadronImportController::class, 'commit'])->name('commit');
        Route::post('/revertir', [PadronImportController::class, 'rollback'])->name('rollback');
        Route::delete('/', [PadronImportController::class, 'destroy'])->name('destroy');
    });
});
