<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Predio extends Model
{
    use HasFactory, HasSpatial;

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
        'estado',
        'lote',
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

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function ventaActiva()
    {
        return $this->hasOne(Venta::class)->where('estado', 'pagando');
    }

    public function observaciones(): HasMany
    {
        return $this->hasMany(PredioObservacion::class);
    }

    /**
     * Centro aproximado del polígono (lat/lng) para mapas estáticos.
     *
     * @return array{lat: float, lng: float}|null
     */
    public function centroMapa(): ?array
    {
        if (! $this->polygon) {
            return null;
        }

        $lats = [];
        $lngs = [];

        foreach ($this->polygon->getGeometries() as $lineString) {
            foreach ($lineString->getGeometries() as $point) {
                $lats[] = $point->latitude;
                $lngs[] = $point->longitude;
            }
        }

        if ($lats === []) {
            return null;
        }

        return [
            'lat' => round(array_sum($lats) / count($lats), 6),
            'lng' => round(array_sum($lngs) / count($lngs), 6),
        ];
    }

    /**
     * Parámetro path para Google Static Maps (contorno del lote en satélite).
     */
    public function rutaPoligonoGoogleStatic(): ?string
    {
        if (! $this->polygon) {
            return null;
        }

        $lineString = $this->polygon->getGeometries()->first();

        if (! $lineString) {
            return null;
        }

        $coords = [];

        foreach ($lineString->getGeometries() as $point) {
            $coords[] = $point->latitude.','.$point->longitude;
        }

        if (count($coords) < 3) {
            return null;
        }

        return 'color:0x15803dff|weight:2|fillcolor:0x15803d33|'.implode('|', $coords);
    }
}
