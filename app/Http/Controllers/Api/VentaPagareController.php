<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Services\Api\PagareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VentaPagareController extends Controller
{
    public function __construct(protected PagareService $pagareService)
    {
    }

    /**
     * Genera los pagarés para una venta y devuelve la información del archivo.
     */
    public function show(Request $request, Venta $venta): JsonResponse
    {
        $file = $this->pagareService->generate($venta);

        if (!$file) {
            return response()->json([
                'message' => 'No hay letras de tipo "letra" pendientes o activas para generar pagarés',
                'file' => null
            ]);
        }

        return response()->json([
            'message' => 'Pagarés generados exitosamente',
            'file' => [
                'id' => $file->id,
                'uuid' => $file->uuid,
                'title' => $file->title,
                'url' => asset(Storage::url($file->path)),
                'path' => $file->path,
                'size' => $file->size,
                'created_at' => $file->created_at,
            ]
        ]);
    }

}
