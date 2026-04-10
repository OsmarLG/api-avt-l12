<?php

use App\Http\Controllers\Api\VentaController;
use Illuminate\Support\Facades\Route;

Route::apiResource('ventas', VentaController::class);
Route::post('ventas/{venta}/cancel', [VentaController::class, 'cancel'])->name('ventas.cancel');
Route::get('ventas/{venta}/pagares', [App\Http\Controllers\Api\VentaPagareController::class, 'show'])->name('ventas.pagares');
Route::patch('ventas/{venta}/cambiar-comprador', [VentaController::class, 'cambiarComprador'])->name('ventas.cambiar-comprador');
