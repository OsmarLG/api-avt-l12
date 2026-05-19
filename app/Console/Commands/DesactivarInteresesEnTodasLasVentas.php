<?php

namespace App\Console\Commands;

use App\Models\Letra;
use App\Models\Venta;
use Illuminate\Console\Command;

class DesactivarInteresesEnTodasLasVentas extends Command
{
    protected $signature = 'app:desactivar-intereses-en-todas-las-ventas';

    protected $description = 'Desactiva intereses moratorios en todas las ventas y limpia intereses ya calculados';

    public function handle(): int
    {
        $total = 0;

        Venta::query()->chunkById(100, function ($ventas) use (&$total) {
            foreach ($ventas as $venta) {
                $venta->update(['intereses_activo' => false]);

                $venta->letrasVencidas()->each(function (Letra $letra) {
                    $letra->intereses()->update(['monto_neto' => 0, 'monto_bruto' => 0]);
                    $letra->update(['saldo' => $letra->getSaldoSinInteres()]);
                });

                $venta->calcularCache();
                $total++;
            }
        });

        $this->info("Intereses desactivados y saldos recalculados en {$total} venta(s).");

        return self::SUCCESS;
    }
}