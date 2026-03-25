<?php

use App\Http\Controllers\Api\FileController;
use Illuminate\Support\Facades\Route;

Route::delete('files/{file}', [FileController::class, 'destroy'])->name('files.destroy');
