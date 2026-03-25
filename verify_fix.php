<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\Venta;
use App\Services\Api\PagareService;

$venta = Venta::find(1);
if (!$venta->folio) {
    $venta->save();
}

echo "Folio: " . $venta->folio . PHP_EOL;

$service = app(PagareService::class);
$file = $service->generate($venta);

echo "File ID: " . $file->id . PHP_EOL;
echo "File Path: " . $file->path . PHP_EOL;
echo "Folio in Service: " . $venta->refresh()->folio . PHP_EOL;
