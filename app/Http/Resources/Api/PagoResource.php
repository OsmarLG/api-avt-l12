<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'monto' => $this->monto,
            'person' => new PersonResource($this->whenLoaded('person')),
            'estado' => $this->estado,
            'comentario_cancelacion' => $this->comentario_cancelacion,
            'cancelled_by' => new UserResource($this->whenLoaded('cancelledBy')),
            'folio' => $this->folio,
            'fecha_pago' => optional($this->fecha_pago)->format('Y-m-d'),
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
            'abonos' => AbonoResource::collection($this->whenLoaded('abonos')),
        ];
    }
}
