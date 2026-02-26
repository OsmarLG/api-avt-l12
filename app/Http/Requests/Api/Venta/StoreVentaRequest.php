<?php

namespace App\Http\Requests\Api\Venta;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'person_id' => ['required', 'exists:people,id'],
            'aval_id' => ['required', 'exists:people,id'],
            'predio_id' => ['required', 'exists:predios,id'],
            'metodo_pago' => ['required', Rule::in(['meses', 'contado'])],
            'costo_lote' => ['required', 'numeric', 'min:0'],
            'enganche' => ['required', 'numeric', 'min:0'],
            'fecha_primer_abono' => ['required', 'date'],
            'meses_a_pagar' => ['required', 'integer', 'min:1'],
        ];
    }
}
