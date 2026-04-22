<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LetraInteres extends Model
{
    use HasFactory;

    protected $table = 'letras_intereses';

    protected $fillable = [
        'letra_id',
        'monto_bruto',
        'monto_neto',
    ];

    protected $casts = [
        'monto_bruto' => 'decimal:2',
        'monto_neto' => 'decimal:2',
    ];

    public function letra(): BelongsTo
    {
        return $this->belongsTo(Letra::class);
    }

    public function descuentos(): HasMany
    {
        return $this->hasMany(LetraInteresDescuento::class, 'letra_interes_id');
    }
}
