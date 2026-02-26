<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'name',
        'shopify_code',
        'fando_code',
        'tax_name',
        'tax',
    ];

    public function provinces(): HasMany
    {
        return $this->hasMany(Province::class);
    }
}
