<?php

namespace App\Http\Requests\Api\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUsersRequest extends FormRequest
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

            // filtros opcionales (si los usas)
            'email' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:255'],

            'sort_by' => ['nullable', 'string', Rule::in(['id', 'name', 'username', 'email', 'created_at'])],
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
