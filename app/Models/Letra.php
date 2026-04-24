<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Letra extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'venta_id',
        'descripcion',
        'monto',
        'fecha_vencimiento',
        'estado',
        'tipo',
        'saldo',
        'consecutivo',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_vencimiento' => 'date',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function abonos(): HasMany
    {
        return $this->hasMany(Abono::class);
    }

    public function intereses(): HasMany
    {
        return $this->hasMany(LetraInteres::class);
    }

    public function montoRestante(): float
    {
        $totalAbonado = $this->abonos()
            ->where('estado', 'activo')
            ->sum('monto');

        return max($this->monto - $totalAbonado, 0);
    }

    public function calcularInteres()
    {
        //sacar el monto por mes 
        //diasVencidos = fecha_vencimineto - hoy
        //interesPorMes = saldo_letra * (venta_porcentaje/100);
        //interesPorDia = interesPorMes / 30
        //interesBruto = letra_saldo + (interesPorDia * diasVencidos)
        //interesNeto = interesBruto - SumatoriadescuentosActivos

        $diasVencidos = $this->fecha_vencimiento->diffInDays(now(), false);

        if ($diasVencidos <= 0) {
            return;
        }
    
        $porcentaje = $this->venta->intereses_porcentaje;
    
        $interesPorMes = $this->saldo * ($porcentaje / 100);
        $interesPorDia = $interesPorMes / 30;
    
        $interesBruto = $interesPorDia * $diasVencidos;
    
        $sumatoriaDescuentos = $this->intereses()
            ->with('descuentos')
            ->get()
            ->flatMap->descuentos
            ->sum("monto_descontado");
    
        $interesNeto = max(0, $interesBruto - $sumatoriaDescuentos);
    
        $this->intereses()->updateOrCreate(
            ['letra_id' => $this->id],
            [
                "monto_bruto" => $interesBruto,
                "monto_neto" => $interesNeto,
            ]
        );

        $this->saldo = $this->getSaldoSinInteres() + $interesNeto;
        $this->save();
    }

    public function getSaldoSinInteres() {
        return $this->montoRestante();
    }
}
