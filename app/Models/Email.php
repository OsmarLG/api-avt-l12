<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Email extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'type',
    ];

    public function emailable(): MorphTo
    {
        return $this->morphTo();
    }
}
