<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pago extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'monto',
        'person_id',
        'estado',
        'comentario_cancelacion',
        'id_cancelo',
        'folio',
        'fecha_pago',
        'user_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_pago' => 'date',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_cancelo');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function abonos(): HasMany
    {
        return $this->hasMany(Abono::class);
    }
}
