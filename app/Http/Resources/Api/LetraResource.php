<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LetraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'venta_id' => $this->venta_id,
            'descripcion' => $this->descripcion,
            "tipo" => $this->tipo,
            "consecutivo" => $this->consecutivo,
            'monto' => $this->monto,
            'fecha_vencimiento' => optional($this->fecha_vencimiento)->format('Y-m-d'),
            'estado' => $this->estado,
            'saldo' => $this->saldo,
            'saldo_sin_interes' => $this->getSaldoSinInteres(),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
            'abonos' => AbonoResource::collection($this->whenLoaded('abonos')),
            "intereses" => $this->whenLoaded("intereses")
        ];
    }
}
