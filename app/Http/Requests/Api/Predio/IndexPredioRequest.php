<?php

namespace App\Http\Requests\Api\Predio;

use Illuminate\Foundation\Http\FormRequest;

class IndexPredioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'sort_by' => ['nullable', 'string', 'in:id,clave_catastral,created_at'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
