<?php

namespace App\Services\Api;

use App\Models\File;
use App\Models\Venta;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class VentaFileService
{
    public function list(Venta $venta)
    {
        return $venta->files()->latest()->get();
    }

    public function upload(Venta $venta, UploadedFile $uploaded, array $meta, ?int $userId = null): File
    {
        $disk = $meta['disk'] ?? 'public';
        $visibility = $meta['visibility'] ?? 'private';

        $path = $uploaded->store('ventas/'.$venta->id, $disk);

        $file = $venta->files()->create([
            'user_id' => $userId,
            'title' => $meta['title'] ?? null,
            'original_name' => $uploaded->getClientOriginalName(),
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $uploaded->getClientMimeType(),
            'size' => $uploaded->getSize(),
            'visibility' => $visibility,
            'tipo' => $meta['tipo'] ?? 'sin_tipo',
        ]);

        return $file->refresh();
    }

    public function delete(File $file): void
    {
        if ($file->disk && $file->path) {
            Storage::disk($file->disk)->delete($file->path);
        }

        $file->delete();
    }
}
