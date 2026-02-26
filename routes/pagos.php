<?php

use App\Http\Controllers\Api\PagoController;
use Illuminate\Support\Facades\Route;

Route::apiResource('pagos', PagoController::class);
Route::post('pagos/{pago}/cancel', [PagoController::class, 'cancel']);
