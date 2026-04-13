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
            'comprador_id' => ['nullable', 'required_without:aval_id', 'exists:people,id'],
            'aval_id' => ['nullable', 'required_without:comprador_id', 'exists:people,id'],
        ];
    }
}
