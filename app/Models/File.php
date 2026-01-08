<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use HasFactory;

    protected $appends = ['url'];

    protected $fillable = [
        'user_id',
        'title',
        'original_name',
        'disk',
        'path',
        'mime_type',
        'size',
        'visibility',
    ];

    protected static function booted(): void
    {
        static::creating(function (File $file) {
            $file->uuid = (string) Str::uuid();
        });
    }

    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): ?string
    {
        if (!$this->disk || !$this->path) {
            return null;
        }

        return Storage::disk($this->disk)->url($this->path);
    }
}
