<?php

namespace App\Http\Requests\Api\Venta;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVentaFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:20480'], // 20MB
            'tipo' => ['required', Rule::in(['contrato_firmado', 'contrato', 'anticipo', 'pagares', 'sin_tipo'])],
            'title' => ['nullable', 'string', 'max:255'],
            'visibility' => ['nullable', Rule::in(['private', 'public'])],
        ];
    }
}
