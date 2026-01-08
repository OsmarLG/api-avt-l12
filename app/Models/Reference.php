<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reference extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'nombres',
        'celular',
        'parentesco',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
