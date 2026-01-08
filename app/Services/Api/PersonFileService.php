<?php

namespace App\Services\Api;

use App\Models\File;
use App\Models\Person;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PersonFileService
{
    public function list(Person $person)
    {
        return $person->files()->latest()->get();
    }

    public function upload(Person $person, UploadedFile $uploaded, array $meta, ?int $userId = null): File
    {
        $disk = $meta['disk'] ?? 'public';
        $visibility = $meta['visibility'] ?? 'private';

        $path = $uploaded->store('people/' . $person->id, $disk);

        $file = $person->files()->create([
            'user_id' => $userId,
            'title' => $meta['title'] ?? null,
            'original_name' => $uploaded->getClientOriginalName(),
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $uploaded->getClientMimeType(),
            'size' => $uploaded->getSize(),
            'visibility' => $visibility,
        ]);

        return $file->refresh();
    }

    public function delete(File $file): void
    {
        // borrar físico
        if ($file->disk && $file->path) {
            Storage::disk($file->disk)->delete($file->path);
        }

        $file->delete();
    }
}
