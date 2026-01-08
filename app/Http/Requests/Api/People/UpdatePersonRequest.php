<?php

namespace App\Http\Requests\Api\People;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePersonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $personId = $this->route('person')->id;

        return [
            'nombres' => ['sometimes', 'required', 'string', 'max:255'],
            'apellido_paterno' => ['sometimes', 'required', 'string', 'max:255'],
            'apellido_materno' => ['nullable', 'string', 'max:255'],
            'sexo' => ['sometimes', 'required', Rule::in(['masculino', 'femenino'])],
            'fecha_nacimiento' => ['sometimes', 'required', 'date'],
            'edad' => ['sometimes', 'required', 'integer', 'min:0'],
            'nacionalidad' => ['sometimes', 'required', Rule::in(['mexicana', 'estadounidense'])],
            'estado_civil' => ['sometimes', 'required', Rule::in(['soltero', 'casado', 'divorciado', 'viudo', 'union_libre'])],
            'curp' => ['nullable', 'string', 'max:255', Rule::unique('people', 'curp')->ignore($personId)],
            'rfc' => ['nullable', 'string', 'max:255', Rule::unique('people', 'rfc')->ignore($personId)],
            'ine' => ['nullable', 'string', 'max:255', Rule::unique('people', 'ine')->ignore($personId)],
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

            'phones' => ['nullable', 'array'],
            'phones.*.id' => ['nullable', 'integer', 'exists:phones,id'],
            'phones.*.number' => ['required', 'string', 'max:255'],
            'phones.*.type' => ['required', Rule::in(['celular', 'casa', 'trabajo'])],

            'emails' => ['nullable', 'array'],
            'emails.*.id' => ['nullable', 'integer', 'exists:emails,id'],
            'emails.*.email' => ['required', 'email', 'max:255'],
            'emails.*.type' => ['required', Rule::in(['personal', 'trabajo'])],

            'references' => ['nullable', 'array'],
            'references.*.id' => ['nullable', 'integer', 'exists:references,id'],
            'references.*.nombres' => ['required', 'string', 'max:255'],
            'references.*.celular' => ['required', 'string', 'max:255'],
            'references.*.parentesco' => ['required', 'string', 'max:255'],
        ];
    }
}
