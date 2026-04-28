<?php

use App\Http\Controllers\Api\LetraController;
use Illuminate\Support\Facades\Route;

Route::apiResource('letras', LetraController::class)->only(['index', 'show', 'update']);
Route::post('letras/batch/discounts', [LetraController::class, 'batchCreateDiscounts']);
Route::get('letras/discounts/by-venta', [LetraController::class, 'getInteresDescuentosByVenta']);
Route::get('letras/discounts/by-folio', [LetraController::class, 'getInteresDescuentosByFolio']);
