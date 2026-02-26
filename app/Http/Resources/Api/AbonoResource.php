<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbonoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pago_id' => $this->pago_id,
            'letra_id' => $this->letra_id,
            'monto' => $this->monto,
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
