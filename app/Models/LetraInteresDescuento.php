<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetraInteresDescuento extends Model
{
    use HasFactory;

    protected $table = 'letras_intereses_descuentos';

    protected $fillable = [
        'letra_interes_id',
        'porcentaje',
        'monto_casacotado',
    ];

    protected $casts = [
        'porcentaje' => 'decimal:2',
        'monto_casacotado' => 'decimal:2',
    ];

    public function letraInteres(): BelongsTo
    {
        return $this->belongsTo(LetraInteres::class);
    }
}
