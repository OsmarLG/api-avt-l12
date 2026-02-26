<?php

use App\Http\Controllers\Api\AbonoController;
use Illuminate\Support\Facades\Route;

Route::apiResource('abonos', AbonoController::class)->only(['index', 'show']);
