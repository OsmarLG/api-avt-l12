<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Un lote de importación: el archivo que se cargó, con qué opciones y qué dejó en la base.
 */
class ImportBatch extends Model
{
    public const ESTADO_PREVISUALIZADO = 'previsualizado';

    public const ESTADO_APLICADO = 'aplicado';

    public const ESTADO_REVERTIDO = 'revertido';

    protected $fillable = [
        'uuid',
        'tipo',
        'estado',
        'archivo_nombre',
        'archivo_hash',
        'zona_id',
        'zona_nombre',
        'user_id',
        'opciones',
        'resumen',
        'aplicado_at',
        'revertido_at',
        'revertido_por',
    ];

    protected $casts = [
        'opciones' => 'array',
        'resumen' => 'array',
        'aplicado_at' => 'datetime',
        'revertido_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $batch) {
            $batch->uuid ??= (string) Str::uuid();
        });
    }

    public function registros(): HasMany
    {
        return $this->hasMany(ImportBatchRecord::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zona_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function revertidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revertido_por');
    }

    public function puedeRevertirse(): bool
    {
        return $this->estado === self::ESTADO_APLICADO;
    }

    /**
     * Conteo de registros creados por modelo, para el reporte y el botón de rollback.
     *
     * @return array<string, int>
     */
    public function conteoCreados(): array
    {
        return $this->registros()
            ->where('accion', ImportBatchRecord::ACCION_CREADO)
            ->selectRaw('model_type, COUNT(*) as total')
            ->groupBy('model_type')
            ->pluck('total', 'model_type')
            ->mapWithKeys(fn ($total, $tipo) => [class_basename($tipo) => (int) $total])
            ->all();
    }
}
