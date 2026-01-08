<?php

namespace App\Http\Requests\Api\People;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'visibility' => ['nullable', Rule::in(['private', 'public'])],
            'file' => ['required', 'file', 'max:20480'], // 20MB
        ];
    }
}
