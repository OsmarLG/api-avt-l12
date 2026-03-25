<?php

use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('reports/bitacora-general', [ReportController::class, 'bitacoraGeneral']);
Route::get('reports/bitacora-zona/{zone}', [ReportController::class, 'bitacoraZona']);
