<?php

namespace App\Services\Api;

use App\Models\Abono;
use App\Models\Zone;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use App\Support\NumberToWords;

class ReportService
{
    /**
     * Genera la Bitácora General de Pagos (Todas las zonas).
     */
    public function generateBitacoraGeneral(string $startDate, string $endDate): string
    {
        App::setLocale('es');
        Carbon::setLocale('es');

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Obtener todos los abonos en el periodo
        $abonos = Abono::with(['letra.venta.comprador', 'letra.venta.predio.zone'])
            ->whereBetween('created_at', [$start, $end])
            ->get();

        // Agrupar por Zona
        $zonasReport = Zone::all()->map(function ($zone) use ($abonos) {
            $abonosZona = $abonos->filter(fn($a) => $a->letra->venta->predio->zona_id === $zone->id);
            
            // Agrupar por Venta (Contrato) dentro de la zona
            $detalles = $abonosZona->groupBy('letra.venta_id')->map(function ($abonosVenta) {
                $venta = $abonosVenta->first()->letra->venta;
                $predio = $venta->predio;
                
                // Formatear pagos: "1, 2 | 24"
                $letrasNumeros = $abonosVenta->map(function ($a) {
                    $desc = $a->letra->descripcion;
                    // Extraer número de "Letra 1/24"
                    preg_match('/Letra\s+(\d+)\//', $desc, $matches);
                    return $matches[1] ?? $a->letra->consecutivo;
                })->sort()->unique()->implode(', ');

                $totalLetras = 0;
                if ($abonosVenta->first()->letra->descripcion) {
                    preg_match('/\/(\d+)/', $abonosVenta->first()->letra->descripcion, $matches);
                    $totalLetras = $matches[1] ?? 0;
                }

                return [
                    'folio' => $venta->folio ?: $venta->id,
                    'cliente' => $venta->comprador->full_name ?? ($venta->comprador->nombres . ' ' . $venta->comprador->apellido_paterno),
                    'clave_catastral' => $predio->clave_catastral,
                    'pagos_display' => $letrasNumeros . ($totalLetras ? " | $totalLetras" : ""),
                    'importe' => $abonosVenta->sum('monto'),
                ];

            })->values();

            return [
                'zona_nombre' => $zone->nombre,
                'responsable' => $zone->dueno_nombre ?: 'Sin Responsable',
                'detalles' => $detalles,
                'subtotal' => $detalles->sum('importe'),
            ];
        });

        $totalGeneral = $zonasReport->sum('subtotal');
        
        $totalLetras = NumberToWords::convert($totalGeneral);

        $pdf = Pdf::loadView('pdfs.bitacora_general', [

            'periodo' => $start->translatedFormat('d \d\e F') . ($start->isSameDay($end) ? '' : ' al ' . $end->translatedFormat('d \d\e F')) . ' de ' . $end->year,
            'fecha_reporte' => Carbon::now()->translatedFormat('l, d \d\e F \d\e Y'),
            'zonas' => $zonasReport,
            'total_general' => $totalGeneral,
            'total_letras' => $totalLetras,
        ])->setPaper('legal', 'landscape');

        return $pdf->output();
    }

    /**
     * Genera la Bitácora de Pagos por Zona específica.
     */
    public function generateBitacoraZona(Zone $zone, string $startDate, string $endDate): string
    {
        App::setLocale('es');
        Carbon::setLocale('es');

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $abonos = Abono::with(['letra.venta.comprador', 'letra.venta.predio'])
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('letra.venta.predio', fn($q) => $q->where('zona_id', $zone->id))
            ->get();

        $detalles = $abonos->groupBy('letra.venta_id')->map(function ($abonosVenta) {
            $venta = $abonosVenta->first()->letra->venta;
            $predio = $venta->predio;
            
            $letrasNumeros = $abonosVenta->map(function ($a) {
                preg_match('/Letra\s+(\d+)\//', $a->letra->descripcion, $matches);
                return $matches[1] ?? $a->letra->consecutivo;
            })->sort()->unique()->implode(', ');

            $totalLetras = 0;
            if ($abonosVenta->first()->letra->descripcion) {
                preg_match('/\/(\d+)/', $abonosVenta->first()->letra->descripcion, $matches);
                $totalLetras = $matches[1] ?? 0;
            }

            return [
                'folio' => $venta->folio ?: $venta->id,
                'cliente' => $venta->comprador->full_name ?? ($venta->comprador->nombres . ' ' . $venta->comprador->apellido_paterno),
                'clave_catastral' => $predio->clave_catastral,

                'lote' => $predio->lote ?: $predio->gid,
                'manzana' => $predio->manzana,
                'pagos_display' => $letrasNumeros . ($totalLetras ? " | $totalLetras" : ""),
                'importe' => $abonosVenta->sum('monto'),
            ];
        })->values();

        $total = $detalles->sum('importe');
        $totalLetras = NumberToWords::convert($total);

        $pdf = Pdf::loadView('pdfs.bitacora_zona', [

            'zona' => $zone,
            'periodo' => $start->translatedFormat('d \d\e F \d\e Y'),
            'fecha_reporte' => Carbon::now()->translatedFormat('l, d \d\e F \d\e Y'),
            'detalles' => $detalles,
            'total' => $total,
            'total_letras' => $totalLetras,
        ])->setPaper('legal', 'portrait');

        return $pdf->output();
    }
}
