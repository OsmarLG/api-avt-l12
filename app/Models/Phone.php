<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Phone extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'type',
    ];

    public function phoneable(): MorphTo
    {
        return $this->morphTo();
    }
}
