<?php

namespace App\Http\Requests\Api\People;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombres' => ['required', 'string', 'max:255'],
            'apellido_paterno' => ['required', 'string', 'max:255'],
            'apellido_materno' => ['nullable', 'string', 'max:255'],
            'sexo' => ['nullable', Rule::in(['masculino', 'femenino'])],
            'fecha_nacimiento' => ['nullable', 'date'],
            'edad' => ['nullable', 'integer', 'min:0'],
            'nacionalidad' => ['nullable', Rule::in(['mexicana', 'estadounidense'])],
            'estado_civil' => ['nullable', Rule::in(['soltero', 'casado', 'divorciado', 'viudo', 'union_libre'])],

            'curp' => ['nullable', 'string', 'max:255', Rule::unique('people', 'curp')],
            'rfc' => ['nullable', 'string', 'max:255', Rule::unique('people', 'rfc')],
            'ine' => ['nullable', 'string', 'max:255', Rule::unique('people', 'ine')],

            'ocupacion_profesion' => ['nullable', 'string', 'max:255'],
            'pais_nacimiento' => ['nullable', 'string', 'max:255'],
            'estado_nacimiento' => ['nullable', 'string', 'max:255'],
            'municipio_nacimiento' => ['nullable', 'string', 'max:255'],
            'localidad_nacimiento' => ['nullable', 'string', 'max:255'],

            'calle' => ['nullable', 'string', 'max:255'],
            'numero_interior' => ['nullable', 'string', 'max:255'],
            'numero_exterior' => ['nullable', 'string', 'max:255'],
            'colonia' => ['nullable', 'string', 'max:255'],
            'codigo_postal' => ['nullable', 'string', 'max:255'],
            'pais_domicilio' => ['nullable', 'string', 'max:255'],
            'estado_domicilio' => ['nullable', 'string', 'max:255'],
            'municipio_domicilio' => ['nullable', 'string', 'max:255'],
            'localidad_domicilio' => ['nullable', 'string', 'max:255'],

            // phones (morph) - en STORE NO debe venir id
            'phones' => ['nullable', 'array'],
            'phones.*.number' => ['required', 'string', 'max:255'],
            'phones.*.type' => ['required', Rule::in(['celular', 'casa', 'trabajo'])],

            // emails (morph) - en STORE NO debe venir id
            'emails' => ['nullable', 'array'],
            'emails.*.email' => ['required', 'email', 'max:255'],
            'emails.*.type' => ['required', Rule::in(['personal', 'trabajo'])],

            // references (hasMany) - en STORE NO debe venir id
            'references' => ['nullable', 'array'],
            'references.*.nombres' => ['required', 'string', 'max:255'],
            'references.*.celular' => ['required', 'string', 'max:255'],
            'references.*.parentesco' => ['required', 'string', 'max:255'],
        ];
    }
}
