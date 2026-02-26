<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Province extends Model
{
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'country_id',
        'name',
        'shopify_code',
        'fando_code',
        'tax_name',
        'tax_type',
        'tax',
        'tax_percentage',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
