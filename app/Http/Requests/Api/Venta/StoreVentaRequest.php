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
            'comprador_id' => ['required_without:person_id', 'exists:people,id'],
            'person_id' => ['required_without:comprador_id', 'exists:people,id'],
            'aval_id' => ['required', 'exists:people,id'],
            'predio_id' => ['required', 'exists:predios,id'],
            'metodo_pago' => ['required', Rule::in(['meses', 'contado'])],
            'costo_lote' => ['required', 'numeric', 'min:0'],
            'enganche' => ['nullable', 'numeric', 'min:0'],
            'fecha_primer_abono' => ['nullable', 'date'],
            'meses_a_pagar' => ['nullable', 'integer', 'min:1'],
            'letras' => ['nullable', 'array'],
            'letras.*.descripcion' => ['required_with:letras', 'string'],
            'letras.*.monto' => ['required_with:letras', 'numeric'],
            'letras.*.consecutivo' => ['required_with:letras', 'integer'],
            'letras.*.tipo' => ['required_with:letras', 'string'],
            'letras.*.fecha_expiracion' => ['required_with:letras', 'date_format:Y-m-d H:i:s,Y-m-d'],
        ];
    }
}
