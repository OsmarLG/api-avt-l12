<?php

namespace App\Http\Requests\Api\People;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonWithFilesRequest extends StorePersonRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = parent::rules();

        $rules['files'] = ['nullable', 'array'];
        $rules['files.*.file'] = ['required', 'file', 'max:1024000']; // 10MB max
        $rules['files.*.title'] = ['nullable', 'string', 'max:255'];
        $rules['files.*.visibility'] = ['nullable', Rule::in(['private', 'public'])];

        return $rules;
    }
}
