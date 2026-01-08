<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'original_name' => $this->original_name,
            'disk' => $this->disk,
            'path' => $this->path,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'visibility' => $this->visibility,
            'url' => $this->url,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
