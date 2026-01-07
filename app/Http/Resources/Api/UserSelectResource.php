<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSelectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $label = $this->name;

        if (!empty($this->username)) {
            $label .= " (@{$this->username})";
        }

        if (!empty($this->email)) {
            $label .= " <{$this->email}>";
        }

        return [
            'value' => $this->id,
            'label' => $label,
        ];
    }
}
