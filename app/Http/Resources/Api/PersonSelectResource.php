<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonSelectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $fullName = trim(implode(' ', array_filter([
            $this->nombres,
            $this->apellido_paterno,
            $this->apellido_materno,
        ])));

        $label = $fullName;

        if (!empty($this->curp)) {
            $label .= " · CURP: {$this->curp}";
        }

        return [
            'value' => $this->id,
            'label' => $label,
        ];
    }
}
