<?php

use App\Http\Controllers\Api\VentaController;
use App\Http\Controllers\Api\VentaFileController;
use Illuminate\Support\Facades\Route;

Route::apiResource('ventas', VentaController::class);
Route::post('ventas/{venta}/cancel', [VentaController::class, 'cancel'])->name('ventas.cancel');
Route::get('ventas/{venta}/pagares', [App\Http\Controllers\Api\VentaPagareController::class, 'show'])->name('ventas.pagares');
Route::patch('ventas/{venta}/cambiar-comprador', [VentaController::class, 'cambiarComprador'])->name('ventas.cambiar-comprador');

// Files
Route::get('ventas/{venta}/files', [VentaFileController::class, 'index'])->name('ventas.files.index');
Route::post('ventas/{venta}/files', [VentaFileController::class, 'store'])->name('ventas.files.store');
Route::delete('ventas/{venta}/files/{file}', [VentaFileController::class, 'destroy'])->name('ventas.files.destroy');
