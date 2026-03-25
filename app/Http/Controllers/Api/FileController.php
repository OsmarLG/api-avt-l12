<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /**
     * Eliminar un archivo físico y su registro en la base de datos.
     */
    public function destroy(File $file): JsonResponse
    {
        try {
            // Eliminar el archivo físico del disco
            if ($file->disk && $file->path && Storage::disk($file->disk)->exists($file->path)) {
                Storage::disk($file->disk)->delete($file->path);
            }

            // Eliminar el registro de la base de datos
            $file->delete();

            return ApiResponse::ok(null, 'Archivo eliminado correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error('Error al eliminar el archivo: ' . $e->getMessage(), 500);
        }
    }
}
