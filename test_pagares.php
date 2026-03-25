<?php

use App\Models\Venta;
use App\Services\Api\PagareService;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $ventaId = 1;
    $venta = Venta::find($ventaId);
    if (!$venta) {
        die("Venta $ventaId not found\n");
    }

    echo "Venta $ventaId found. Folio: {$venta->folio}\n";
    echo "Comprador: " . ($venta->person_id ? 'Yes' : 'No') . " ({$venta->person_id})\n";
    echo "Aval: " . ($venta->aval_id ? 'Yes' : 'No') . " ({$venta->aval_id})\n";
    echo "Letras Count: " . $venta->letras()->count() . "\n";

    $service = app(PagareService::class);
    $file = $service->generate($venta);

    echo "Success! File ID: {$file->id}, Path: {$file->path}\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
