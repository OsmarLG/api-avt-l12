<?php

use App\Http\Controllers\Api\PersonController;
use App\Http\Controllers\Api\PersonFileController;
use App\Http\Controllers\Api\PersonContactController;
use App\Http\Controllers\PersonReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('people')->group(function () {
    Route::get('/select', [PersonController::class, 'select']);
    Route::get('/options', [PersonController::class, 'options']);

    Route::get('/', [PersonController::class, 'index']);
    Route::get('/{person}', [PersonController::class, 'show']);
    Route::post('/', [PersonController::class, 'store']);
    Route::post('/with-files', [PersonController::class, 'storeWithFiles']);
    Route::put('/{person}', [PersonController::class, 'update']);
    Route::delete('/{person}', [PersonController::class, 'destroy']);
    Route::get('/{person}/report', [PersonReportController::class, 'show']);

    Route::prefix('{person}')->group(function () {
        // files
        Route::get('/files', [PersonFileController::class, 'index']);
        Route::post('/files', [PersonFileController::class, 'store']);

        // phones
        Route::post('/phones', [PersonContactController::class, 'addPhone']);
        Route::put('/phones/{phone}', [PersonContactController::class, 'updatePhone']);
        Route::delete('/phones/{phone}', [PersonContactController::class, 'deletePhone']);

        // emails
        Route::post('/emails', [PersonContactController::class, 'addEmail']);
        Route::put('/emails/{email}', [PersonContactController::class, 'updateEmail']);
        Route::delete('/emails/{email}', [PersonContactController::class, 'deleteEmail']);
    });
});

Route::delete('/files/{file}', [PersonFileController::class, 'destroy']);
