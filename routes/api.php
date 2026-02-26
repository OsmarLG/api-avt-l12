<?php

require __DIR__ . '/auth.php';

Route::middleware(['auth:sanctum'])->group(function () {
    require __DIR__ . '/users.php';
    require __DIR__ . '/people.php';
    require __DIR__ . '/zones.php';
    require __DIR__ . '/predios.php';
    require __DIR__ . '/ventas.php';
    require __DIR__ . '/pagos.php';
    require __DIR__ . '/letras.php';
    require __DIR__ . '/abonos.php';
});
