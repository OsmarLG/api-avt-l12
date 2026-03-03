<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Abono extends Model
{
    use HasFactory;

    protected $fillable = [
        'pago_id',
        'letra_id',
        'monto',
        "estado",
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class);
    }

    public function letra(): BelongsTo
    {
        return $this->belongsTo(Letra::class);
    }
}
