<?php

namespace App\Http\Requests\Api\Venta;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'aval_id' => ['nullable', 'exists:people,id'],
            'comentario_cancelacion' => ['nullable', 'string', 'max:1000'],
            // Financial data usually NOT editable after creation to preserve integrity
            // But we can allow basic fields if needed
        ];
    }
}
