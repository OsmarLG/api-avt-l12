<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Zone;

class Predio extends Model
{
    use HasSpatial;

    protected $fillable = [
        'clave_catastral',
        'polygon',
        'gid',
        'condicion',
        'tipo_predio',
        'activo',
        'propietario',
        'ubicacion',
        'sup_cons',
        'sup_terr',
        'vc',
        'vt',
        'tasa',
        'manzana',
        'area',
        'zona_id',
        'estado'
    ];

    protected $casts = [
        'polygon' => Polygon::class,
    ];

    const ESTADO_DISPONIBLE = 'disponible';
    const ESTADO_PAGANDO = 'pagando';
    const ESTADO_PAGADO = 'pagado';

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zona_id');
    }
}
