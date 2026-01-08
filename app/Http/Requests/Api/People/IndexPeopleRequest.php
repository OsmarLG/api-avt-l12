<?php

namespace App\Http\Requests\Api\People;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPeopleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],

            'search' => ['nullable', 'string', 'max:255'],

            // filtros opcionales
            'curp' => ['nullable', 'string', 'max:255'],
            'rfc' => ['nullable', 'string', 'max:255'],
            'ine' => ['nullable', 'string', 'max:255'],
            'nombres' => ['nullable', 'string', 'max:255'],
            'apellido_paterno' => ['nullable', 'string', 'max:255'],
            'apellido_materno' => ['nullable', 'string', 'max:255'],

            'sort_by' => ['nullable', 'string', Rule::in(['id', 'nombres', 'apellido_paterno', 'apellido_materno', 'created_at'])],
            'sort_dir' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sort_by' => $this->input('sort_by', 'id'),
            'sort_dir' => $this->input('sort_dir', 'desc'),
        ]);
    }
}
