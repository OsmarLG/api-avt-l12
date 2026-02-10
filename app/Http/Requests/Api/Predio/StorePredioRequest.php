<?php

namespace App\Http\Requests\Api\Predio;

use Illuminate\Foundation\Http\FormRequest;

class StorePredioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clave_catastral' => ['required', 'string', 'max:255', 'unique:predios,clave_catastral'],
            'propietario' => ['nullable', 'string', 'max:255'],
            'ubicacion' => ['nullable', 'string', 'max:255'],
            'sup_cons' => ['nullable', 'numeric'],
            'sup_terr' => ['nullable', 'numeric'],
            'condicion' => ['nullable', 'string', 'max:50'],
            'tipo_predio' => ['nullable', 'string', 'max:50'],
            'zona_id' => ['nullable', 'exists:zones,id'],
            'polygon' => ['nullable'],
            'gid' => ['nullable', 'numeric'],
            'activo' => ['nullable', 'string', 'max:255'],
            'vc' => ['nullable', 'numeric'],
            'vt' => ['nullable', 'numeric'],
            'tasa' => ['nullable', 'numeric'],
            'manzana' => ['nullable', 'string', 'max:255'],
            'area' => ['nullable', 'numeric'],
        ];
    }
}
