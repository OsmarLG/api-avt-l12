<?php

namespace App\Http\Requests\Api\PredioObservacion;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePredioObservacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'observacion' => ['required', 'string', 'max:5000'],
        ];
    }
}
