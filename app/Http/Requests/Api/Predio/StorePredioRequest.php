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
            'gid' => ['nullable', 'numeric'],
            'activo' => ['nullable', 'string', 'max:255'],
            'vc' => ['nullable', 'numeric'],
            'vt' => ['nullable', 'numeric'],
            'tasa' => ['nullable', 'numeric'],
            'manzana' => ['nullable', 'string', 'max:255'],
            'area' => ['nullable', 'numeric'],

            // GeoJSON-like
            'geometry' => ['nullable', 'array'],
            'geometry.type' => ['required_with:geometry', 'in:Polygon,MultiPolygon'],
            'geometry.coordinates' => ['required_with:geometry', 'array'],

            // Opcional: si quieres permitir polygon simple como [ [lat,lng], [lat,lng], ... ]
            'polygon' => ['nullable', 'array'],
            'polygon.*' => ['array', 'size:2'],
            'polygon.*.0' => ['numeric'], // lat
            'polygon.*.1' => ['numeric'], // lng
        ];
    }
}
