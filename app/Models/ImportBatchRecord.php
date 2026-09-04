<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Una fila de la bitácora: qué modelo se creó, actualizó o reutilizó, y desde qué fila del Excel.
 */
class ImportBatchRecord extends Model
{
    public const ACCION_CREADO = 'creado';

    public const ACCION_ACTUALIZADO = 'actualizado';

    public const ACCION_REUTILIZADO = 'reutilizado';

    protected $fillable = [
        'import_batch_id',
        'fila',
        'model_type',
        'model_id',
        'accion',
        'referencia',
        'datos_previos',
    ];

    protected $casts = [
        'datos_previos' => 'array',
        'fila' => 'integer',
        'model_id' => 'integer',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function model(): MorphTo
    {
        return $this->morphTo(null, 'model_type', 'model_id');
    }
}
