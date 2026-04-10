<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Venta\StoreVentaFileRequest;
use App\Http\Resources\Api\FileResource;
use App\Models\File;
use App\Models\Venta;
use App\Services\Api\VentaFileService;
use App\Support\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class VentaFileController extends Controller
{
    public function __construct(
        private readonly VentaFileService $service
    ) {}

    /**
     * List files for a venta.
     */
    public function index(Venta $venta)
    {
        $files = $this->service->list($venta);

        return ApiResponse::ok(FileResource::collection($files));
    }

    /**
     * Upload and attach a file to a venta.
     *
     * Supported types: contrato_firmado, contrato, anticipo, pagares, sin_tipo.
     */
    public function store(StoreVentaFileRequest $request, Venta $venta)
    {
        $data = $request->validated();

        $file = $this->service->upload(
            venta: $venta,
            uploaded: $request->file('file'),
            meta: $data,
            userId: $request->user()?->id,
        );

        return ApiResponse::ok(new FileResource($file), 'Archivo subido correctamente', Response::HTTP_CREATED);
    }

    /**
     * Delete a file from a venta.
     */
    public function destroy(Venta $venta, File $file)
    {
        $this->service->delete($file);

        return ApiResponse::ok(null, 'Archivo eliminado correctamente');
    }
}
