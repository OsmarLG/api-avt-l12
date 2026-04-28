<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LetraInteresDescuentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'letra_interes_id' => $this->letra_interes_id,
            'porcentaje' => $this->porcentaje,
            'monto_descontado' => $this->monto_descontado,
            'comentario' => $this->comentario,
            'estado' => $this->estado,
            'folio' => $this->folio,
            'created_by' => $this->created_by,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
