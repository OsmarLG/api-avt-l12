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
            'venta_id' => ['required', 'exists:ventas,id'],
            'metodo_pago' => ['required', 'in:efectivo,tarjeta_debito,tarjeta_credito,cheque,transferencia'],
        ];
    }
}
