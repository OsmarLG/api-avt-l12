<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PredioObservacion extends Model
{
    use HasFactory;

    protected $table = 'predios_observaciones';

    protected $fillable = [
        'predio_id',
        'observacion',
    ];

    public function predio(): BelongsTo
    {
        return $this->belongsTo(Predio::class);
    }
}
