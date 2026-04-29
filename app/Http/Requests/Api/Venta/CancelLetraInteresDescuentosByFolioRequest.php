<?php

namespace App\Http\Requests\Api\Venta;

use Illuminate\Foundation\Http\FormRequest;

class CancelLetraInteresDescuentosByFolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'folio' => ['required', 'string', 'exists:letras_intereses_descuentos,folio'],
        ];
    }

    public function messages(): array
    {
        return [
            'folio.required' => 'El campo folio es requerido',
            'folio.exists' => 'El folio especificado no existe',
        ];
    }
}
