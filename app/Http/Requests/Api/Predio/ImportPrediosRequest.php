<?php

namespace App\Http\Requests\Api\Predio;

use Illuminate\Foundation\Http\FormRequest;

class ImportPrediosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'claves_catastrales' => ['required', 'array'],
            'claves_catastrales.*' => ['string'],
        ];
    }
}
