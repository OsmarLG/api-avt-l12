<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombres' => $this->nombres,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'sexo' => $this->sexo,
            'fecha_nacimiento' => optional($this->fecha_nacimiento)->format('Y-m-d'),
            'edad' => $this->edad,
            'nacionalidad' => $this->nacionalidad,
            'estado_civil' => $this->estado_civil,
            'curp' => $this->curp,
            'rfc' => $this->rfc,
            'ine' => $this->ine,
            'ocupacion_profesion' => $this->ocupacion_profesion,
            'pais_nacimiento' => $this->pais_nacimiento,
            'estado_nacimiento' => $this->estado_nacimiento,
            'municipio_nacimiento' => $this->municipio_nacimiento,
            'localidad_nacimiento' => $this->localidad_nacimiento,
            'calle' => $this->calle,
            'numero_interior' => $this->numero_interior,
            'numero_exterior' => $this->numero_exterior,
            'colonia' => $this->colonia,
            'codigo_postal' => $this->codigo_postal,
            'pais_domicilio' => $this->pais_domicilio,
            'estado_domicilio' => $this->estado_domicilio,
            'municipio_domicilio' => $this->municipio_domicilio,
            'localidad_domicilio' => $this->localidad_domicilio,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
            'phones' => PhoneResource::collection($this->whenLoaded('phones')),
            'emails' => EmailResource::collection($this->whenLoaded('emails')),
            'references' => ReferenceResource::collection($this->whenLoaded('references')),
            'files' => FileResource::collection($this->whenLoaded('files')),
        ];
    }
}
