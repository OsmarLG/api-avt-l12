<?php

use App\Http\Controllers\Api\PagoController;
use Illuminate\Support\Facades\Route;

Route::get('pagos/filter', [PagoController::class, 'pagosFilterWithoutPagination']);
Route::apiResource('pagos', PagoController::class);
Route::post('pagos/{pago}/cancel', [PagoController::class, 'cancel']);

