<?php

namespace App\Http\Resources\Api;

use App\Models\Predio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\ZoneResource;

/** @mixin Predio */
class PredioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'clave_catastral' => $this->clave_catastral,
            // 'polygon' => $this->polygon, // geometry might be too heavy to always return?
            'gid' => $this->gid,
            'condicion' => $this->condicion,
            'tipo_predio' => $this->tipo_predio,
            'activo' => $this->activo,
            'propietario' => $this->propietario,
            'ubicacion' => $this->ubicacion,
            'sup_cons' => $this->sup_cons,
            'sup_terr' => $this->sup_terr,
            'vc' => $this->vc,
            'vt' => $this->vt,
            'tasa' => $this->tasa,
            'manzana' => $this->manzana,
            'area' => $this->area,
            'zona_id' => $this->zona_id,
            'zona' => new ZoneResource($this->whenLoaded('zona')),
        ];
    }
}
