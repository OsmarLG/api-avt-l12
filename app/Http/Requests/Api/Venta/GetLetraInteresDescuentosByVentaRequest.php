<?php

namespace App\Http\Requests\Api\Venta;

use Illuminate\Foundation\Http\FormRequest;

class GetLetraInteresDescuentosByVentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'venta_id' => ['required', 'integer', 'exists:ventas,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'venta_id.required' => 'El campo venta_id es requerido',
            'venta_id.exists' => 'La venta especificada no existe',
        ];
    }
}
