<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Zone extends Model
{
    use HasSpatial;

    protected $fillable = [
        'nombre',
        'dueno_nombre',
        'ubicacion',
    ];

    protected $casts = [
        'ubicacion' => Point::class,
    ];

    public function predios(): HasMany
    {
        return $this->hasMany(Predio::class);
    }
}
