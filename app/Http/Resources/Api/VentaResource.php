<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VentaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'comprador' => new PersonResource($this->whenLoaded('comprador')),
            'aval' => new PersonResource($this->whenLoaded('aval')),
            'predio' => new PredioResource($this->whenLoaded('predio')),
            'estado' => $this->estado,
            'user' => new UserResource($this->whenLoaded('user')),
            'metodo_pago' => $this->metodo_pago,
            'costo_lote' => $this->costo_lote,
            'folio' => $this->folio,
            'enganche' => $this->enganche,
            'fecha_primer_abono' => optional($this->fecha_primer_abono)->format('Y-m-d'),
            'meses_a_pagar' => $this->meses_a_pagar,
            'cancelled_by' => new UserResource($this->whenLoaded('cancelledBy')),
            'comentario_cancelacion' => $this->comentario_cancelacion,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
            'letras' => LetraResource::collection($this->whenLoaded('letras')),
            "monto_restante_letra" => $this->monto_restante_letra,
            "proxima_letra_id" => $this->proxima_letra_id,
            "proxima_letra" => new LetraResource($this->whenLoaded('proximaLetra')),
            'files' => FileResource::collection($this->whenLoaded('files')),
        ];
    }
}
