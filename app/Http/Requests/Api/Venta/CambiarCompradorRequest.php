<?php

namespace App\Http\Requests\Api\Venta;

use Illuminate\Foundation\Http\FormRequest;

class CambiarCompradorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comprador_id' => ['required', 'exists:people,id'],
            'aval_id' => ['nullable', 'exists:people,id'],
        ];
    }
}
