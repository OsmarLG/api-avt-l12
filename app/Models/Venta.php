<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venta extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'person_id',
        'aval_id',
        'predio_id',
        'estado',
        'user_id',
        'metodo_pago',
        'costo_lote',
        'enganche',
        'fecha_primer_abono',
        'meses_a_pagar',
        'id_cancelo',
        'comentario_cancelacion',
    ];

    protected $casts = [
        'costo_lote' => 'decimal:2',
        'enganche' => 'decimal:2',
        'fecha_primer_abono' => 'date',
        'meses_a_pagar' => 'integer',
    ];

    public function comprador(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function aval(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'aval_id');
    }

    public function predio(): BelongsTo
    {
        return $this->belongsTo(Predio::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_cancelo');
    }

    public function letras(): HasMany
    {
        return $this->hasMany(Letra::class);
    }
}
