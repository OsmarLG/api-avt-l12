<?php

namespace App\Http\Requests\Imports;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Carga del Excel y opciones con las que se simula la importación.
 */
class PreviewPadronImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Las rutas del importador ya viven detrás del middleware `auth`.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
            'zona_id' => ['nullable', 'integer', 'exists:zones,id'],
            'zona_nombre' => ['required_without:zona_id', 'nullable', 'string', 'max:255'],
            'zona_dueno' => ['nullable', 'string', 'max:255'],
            'folio_prefijo' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9]*$/'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'geojson_path' => ['nullable', 'string', 'max:1024'],
            'superficie_desde_excel' => ['nullable', 'boolean'],
            'importar_telefonos' => ['nullable', 'boolean'],
            'permitir_sin_poligono' => ['nullable', 'boolean'],
            'generar_documentos' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'archivo.required' => 'Selecciona el archivo del padrón.',
            'archivo.mimes' => 'El padrón debe ser un archivo .xlsx o .xls.',
            'archivo.max' => 'El archivo no puede pesar más de 20 MB.',
            'zona_nombre.required_without' => 'Escribe el nombre de la zona o elige una existente.',
            'folio_prefijo.regex' => 'El prefijo del folio sólo admite letras y números.',
        ];
    }

    /**
     * Opciones listas para PadronImportService.
     *
     * @return array<string, mixed>
     */
    public function opciones(): array
    {
        return [
            'zona_id' => $this->filled('zona_id') ? (int) $this->input('zona_id') : null,
            'zona_nombre' => $this->input('zona_nombre'),
            'zona_dueno' => $this->input('zona_dueno'),
            'folio_prefijo' => $this->input('folio_prefijo'),
            'user_id' => (int) $this->input('user_id'),
            'geojson_path' => $this->input('geojson_path') ?: null,
            'superficie_desde_excel' => $this->boolean('superficie_desde_excel'),
            'importar_telefonos' => $this->boolean('importar_telefonos'),
            'permitir_sin_poligono' => $this->boolean('permitir_sin_poligono'),
            'generar_documentos' => $this->boolean('generar_documentos'),
        ];
    }
}
