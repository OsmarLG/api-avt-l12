<?php

use App\Http\Controllers\Api\VentaController;
use Illuminate\Support\Facades\Route;

Route::apiResource('ventas', VentaController::class);
Route::post('ventas/{venta}/cancel', [VentaController::class, 'cancel']);
