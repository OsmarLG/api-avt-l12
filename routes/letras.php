<?php

use App\Http\Controllers\Api\LetraController;
use Illuminate\Support\Facades\Route;

Route::apiResource('letras', LetraController::class)->only(['index', 'show', 'update']);
