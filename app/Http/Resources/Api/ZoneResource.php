<?php

namespace App\Http\Resources\Api;

use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\PredioResource;

/** @mixin Zone */
class ZoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'dueno_nombre' => $this->dueno_nombre,
            'predios' => PredioResource::collection($this->whenLoaded('predios')),
        ];
    }
}
