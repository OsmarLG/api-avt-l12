<?php

namespace App\Services\Api;

use App\Models\Abono;
use App\Models\Letra;
use App\Models\Person;
use App\Models\Zone;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
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
            ->where("estado","activo")
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
                    if($a->letra->descripcion == "ANTICIPO"){
                        return "ANT";
                    }else {
                        // Extraer número de "Letra 1/24"
                        preg_match('/Letra\s+(\d+)\//', $a->letra->descripcion, $matches);
                        return $matches[1] ?? $a->letra->consecutivo;
                    }
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
        ]);

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
            ->where("estado","activo")
            ->whereHas('letra.venta.predio', fn($q) => $q->where('zona_id', $zone->id))
            ->get();

        $detalles = $abonos->groupBy('letra.venta_id')->map(function ($abonosVenta) {
            $venta = $abonosVenta->first()->letra->venta;
            $predio = $venta->predio;
            
            $letrasNumeros = $abonosVenta->map(function ($a) {
                if($a->letra->descripcion == "ANTICIPO"){
                    return "ANT";
                }else {
                    // Extraer número de "Letra 1/24"
                    preg_match('/Letra\s+(\d+)\//', $a->letra->descripcion, $matches);
                    return $matches[1] ?? $a->letra->consecutivo;
                }
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

    /**
     * Personas con al menos una venta activa que tenga letras pendientes vencidas.
     * Una fila por venta (todas las letras vencidas del contrato en el mismo renglón).
     *
     * Con periodo opcional: solo ventas cuya primera letra vencida (menor fecha_vencimiento
     * entre letras pendientes con vencimiento anterior a hoy) cae entre startDate y endDate.
     */
    public function generateReporteCompradoresMorosos(?string $startDate = null, ?string $endDate = null): string
    {
        App::setLocale('es');
        Carbon::setLocale('es');

        $asOf = Carbon::now()->startOfDay();
        $periodStart = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
        $periodEnd = $endDate ? Carbon::parse($endDate)->endOfDay() : null;
        $conPeriodo = $periodStart && $periodEnd;

        $personas = Person::query()
            ->whereHas('ventas', function ($q) use ($asOf, $periodStart, $periodEnd, $conPeriodo) {
                $this->aplicarFiltroVentasMorosas($q, $asOf, $periodStart, $periodEnd, $conPeriodo);
            })
            ->with([
                'phones',
                'emails',
                'ventas' => function ($q) use ($asOf, $periodStart, $periodEnd, $conPeriodo) {
                    $this->aplicarFiltroVentasMorosas($q, $asOf, $periodStart, $periodEnd, $conPeriodo);
                    $q->orderBy('folio');
                },
                'ventas.letras' => function ($q) use ($asOf) {
                    $q->where('estado', 'pendiente')
                        ->where('fecha_vencimiento', '<', $asOf)
                        ->orderBy('fecha_vencimiento')
                        ->orderBy('id');
                },
            ])
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres')
            ->get();

        $filas = collect();
        $totalLetrasVencidas = 0;

        foreach ($personas as $person) {
            $nombre = trim((string) ($person->full_name ?? ''));
            if ($nombre === '') {
                $nombre = trim("{$person->nombres} {$person->apellido_paterno}");
            }

            $telefonos = $person->phones->pluck('number')->filter()->unique()->implode(', ');
            $correos = $person->emails->pluck('email')->filter()->unique()->implode(', ');

            foreach ($person->ventas as $venta) {
                $letrasVenc = $venta->letras;
                if ($letrasVenc->isEmpty()) {
                    continue;
                }

                $folio = $venta->folio ?: (string) $venta->id;

                $nums = $letrasVenc->map(fn (Letra $l) => $this->numeroLetraMoroso($l))->unique()->values();
                $numsOrdenados = $nums->sortBy(function ($n) {
                    if ($n === 'ANT') {
                        return -1;
                    }

                    return is_numeric($n) ? (float) $n : 9999;
                })->values()->implode(', ');

                $primeraVencida = $letrasVenc->sortBy(fn (Letra $l) => $l->fecha_vencimiento->timestamp)->first();
                $fechaPrimeraVencida = $primeraVencida->fecha_vencimiento->translatedFormat('d \d\e F \d\e Y');

                $totalLetrasVencidas += $letrasVenc->count();

                $filas->push([
                    'nombre' => $nombre,
                    'numero_letra' => $numsOrdenados,
                    'folio_contrato' => $folio,
                    'fecha_vencimiento' => $fechaPrimeraVencida,
                    'telefono' => $telefonos !== '' ? $telefonos : '—',
                    'correo' => $correos !== '' ? $correos : '—',
                ]);
            }
        }

        $periodoLabel = $conPeriodo
            ? 'Primera letra vencida del '.$periodStart->translatedFormat('d \d\e F \d\e Y')
                .' al '.$periodEnd->translatedFormat('d \d\e F \d\e Y')
                .' (corte al '.$asOf->translatedFormat('d \d\e F \d\e Y').')'
            : 'Letras vencidas al '.$asOf->translatedFormat('d \d\e F \d\e Y');

        $pdf = Pdf::loadView('pdfs.reporte_compradores_morosos', [
            'periodo' => $periodoLabel,
            'fecha_reporte' => Carbon::now()->translatedFormat('l, d \d\e F \d\e Y'),
            'filas' => $filas,
            'total_contratos' => $filas->count(),
            'total_letras_vencidas' => $totalLetrasVencidas,
        ]);

        return $pdf->output();
    }

    /**
     * Venta morosa: no cancelada, con letras pendientes vencidas (antes de hoy).
     * Con periodo: la fecha de la primera de esas letras debe estar en el rango.
     */
    private function aplicarFiltroVentasMorosas($query, Carbon $asOf, ?Carbon $periodStart, ?Carbon $periodEnd, bool $conPeriodo): void
    {
        $query->where('estado', '!=', 'cancelado')
            ->whereHas('letras', function ($q2) use ($asOf) {
                $q2->where('estado', 'pendiente')
                    ->where('fecha_vencimiento', '<', $asOf);
            });

        if ($conPeriodo) {
            $query->whereRaw(
                '(SELECT MIN(fecha_vencimiento) FROM letras WHERE letras.venta_id = ventas.id AND letras.estado = ? AND letras.fecha_vencimiento < ?) BETWEEN ? AND ?',
                ['pendiente', $asOf->toDateString(), $periodStart->toDateString(), $periodEnd->toDateString()]
            );
        }
    }

    private function numeroLetraMoroso(Letra $letra): string
    {
        $desc = $letra->descripcion;

        if ($desc === 'ANTICIPO') {
            return 'ANT';
        }

        if ($desc && preg_match('/Letra\s+(\d+)\//', $desc, $m)) {
            return $m[1];
        }

        if ($desc && preg_match('/Letra\s+(\d+)\s+de\s+/i', $desc, $m)) {
            return $m[1];
        }

        return (string) ($letra->consecutivo ?? '');
    }
}
