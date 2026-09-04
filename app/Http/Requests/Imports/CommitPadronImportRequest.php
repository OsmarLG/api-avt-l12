<?php

namespace App\Http\Requests\Imports;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Confirmación explícita antes de escribir el padrón en la base.
 */
class CommitPadronImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'confirmacion' => ['required', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirmacion.required' => 'Marca la casilla de confirmación antes de aplicar la importación.',
            'confirmacion.accepted' => 'Marca la casilla de confirmación antes de aplicar la importación.',
        ];
    }
}
