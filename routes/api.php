<?php

use Illuminate\Support\Facades\Route;

require __DIR__ . '/auth.php';

Route::middleware(['auth:sanctum'])->group(function () {
    require __DIR__ . '/users.php';
    require __DIR__ . '/people.php';
    require __DIR__ . '/zones.php';
});
