<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Letra extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'venta_id',
        'descripcion',
        'monto',
        'fecha_vencimiento',
        'estado',
        "tipo",
        "fecha_expiracion"
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_vencimiento' => 'date',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function abonos(): HasMany
    {
        return $this->hasMany(Abono::class);
    }
}
