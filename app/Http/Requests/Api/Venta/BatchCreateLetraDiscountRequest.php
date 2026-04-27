<?php

namespace App\Http\Requests\Api\Venta;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BatchCreateLetraDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'discounts' => ['required', 'array', 'min:1'],
            'discounts.*.letra_id' => ['required', 'integer', 'exists:letras,id'],
            'discounts.*.porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'discounts.*.monto_descontado' => ['required', 'numeric', 'min:0'],
            'discounts.*.comentario' => ['nullable', 'string', 'max:255'],
            'discounts.*.estado' => ['nullable', Rule::in(['activo', 'cancelado'])],
        ];
    }

    public function messages(): array
    {
        return [
            'discounts.required' => 'El campo discounts es requerido',
            'discounts.array' => 'El campo discounts debe ser un array',
            'discounts.min' => 'Debe proporcionar al menos un descuento',
            'discounts.*.letra_id.required' => 'El letra_id es requerido en cada descuento',
            'discounts.*.letra_id.exists' => 'La letra especificada no existe',
            'discounts.*.porcentaje.required' => 'El porcentaje es requerido',
            'discounts.*.monto_descontado.required' => 'El monto_descontado es requerido',
        ];
    }
}
