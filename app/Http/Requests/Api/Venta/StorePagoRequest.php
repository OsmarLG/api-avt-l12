<?php

namespace App\Http\Requests\Api\Venta;

use Illuminate\Foundation\Http\FormRequest;

class StorePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto' => ['required', 'numeric', 'min:0.01'],
            'person_id' => ['required', 'exists:people,id'],
            'fecha_pago' => ['required', 'date'],
            'folio' => ['nullable', 'string', 'max:255'],
            'abonos' => ['required', 'array', 'min:1'],
            'abonos.*.letra_id' => ['required', 'exists:letras,id'],
            'abonos.*.monto' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
