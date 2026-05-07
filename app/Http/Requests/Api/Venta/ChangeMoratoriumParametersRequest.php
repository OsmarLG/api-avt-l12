<?php

namespace App\Http\Requests\Api\Venta;

use Illuminate\Foundation\Http\FormRequest;

class ChangeMoratoriumParametersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'intereses_porcentaje'  => ['sometimes', 'numeric', 'min:0'],
            'intereses_dias_tregua' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
