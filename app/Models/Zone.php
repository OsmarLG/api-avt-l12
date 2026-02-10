<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Predio;

class Zone extends Model
{
    protected $fillable = [
        'nombre',
        'dueno_nombre',
    ];

    public function predios(): HasMany
    {
        return $this->hasMany(Predio::class);
    }
}
