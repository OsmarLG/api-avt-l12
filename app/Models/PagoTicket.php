<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoTicket extends Model
{
    use HasFactory;

    protected $table = 'pagos_tickets';

    protected $fillable = [
        'pago_id',
        'ticket',
    ];

    protected $casts = [
        'ticket' => 'array',
    ];

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class);
    }
}

