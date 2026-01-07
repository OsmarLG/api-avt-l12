<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'App' => config('app.name'),
        'PHP Version' => PHP_VERSION,
        'Laravel Version' => app()->version(),
        'Environment' => config('app.env'),
        'message' => 'API is running'
    ]);
});
