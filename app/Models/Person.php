<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'sexo',
        'fecha_nacimiento',
        'edad',
        'nacionalidad',
        'estado_civil',
        'curp',
        'rfc',
        'ine',
        'ocupacion_profesion',
        'pais_nacimiento',
        'estado_nacimiento',
        'municipio_nacimiento',
        'localidad_nacimiento',
        'calle',
        'numero_interior',
        'numero_exterior',
        'colonia',
        'codigo_postal',
        'pais_domicilio',
        'estado_domicilio',
        'municipio_domicilio',
        'localidad_domicilio',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
        ];
    }

    public function phones(): MorphMany
    {
        return $this->morphMany(Phone::class, 'phoneable');
    }

    public function emails(): MorphMany
    {
        return $this->morphMany(Email::class, 'emailable');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function references(): HasMany
    {
        return $this->hasMany(Reference::class);
    }
}
