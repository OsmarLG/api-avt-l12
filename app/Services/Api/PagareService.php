<?php

namespace App\Services\Api;

use App\Models\File;
use App\Models\Venta;
use App\Support\NumberToWords;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PagareService
{
    /**
     * Genera los pagarés para una venta.
     */
    public function generate(Venta $venta): ?File
    {
        // Asegurar que la venta tenga un folio (Número de Contrato)
        if (!$venta->folio) {
            $venta->save(); // Esto disparará el listener 'saving' definido en el modelo
        }

        // Forzar locale a español para las fechas
        App::setLocale('es');
        \Carbon\Carbon::setLocale('es');

        $venta->load(['comprador', 'aval', 'letras' => fn($q) => $q->orderBy('consecutivo')]);


        $pagares = $venta->letras
            ->where('tipo', 'letra')
            ->where('estado', '!=', 'cancelado')
            ->map(function ($letra) use ($venta) {
                // Limpiar descripción para el número: "Letra 1/24" -> "1 | 24"
                $numeroDisplay = str_replace(['Letra ', '/'], ['', ' | '], $letra->descripcion);

                return [
                    'numero_display' => $numeroDisplay,
                    'fecha_expedicion' => $venta->created_at,
                    'fecha_vencimiento' => $letra->fecha_vencimiento,
                    'folio' => $venta->folio,
                    'monto' => $letra->monto,
                    'monto_letras' => NumberToWords::convert($letra->monto),
                    'total_letras' => $venta->letras->where('tipo', 'letra')->count(),
                ];
            });

        if ($pagares->isEmpty()) {
            return null;
        }

        $pdf = Pdf::loadView('pdfs.pagares', [

            'venta' => $venta,
            'pagares' => $pagares,
            'acreedor' => 'JOSE CASTRO COTA', // Actualizado
        ])->setPaper('legal', 'portrait');


        $fileName = "pagares_{$venta->folio}_" . Str::slug($venta->comprador->full_name ?? 'venta') . ".pdf";
        $path = "ventas/{$venta->id}/{$fileName}";
        $disk = 'public';

        Storage::disk($disk)->put($path, $pdf->output());

        return $venta->files()->create([
            'user_id' => auth()->id() ?? $venta->user_id,
            'title' => 'Pagarés',
            'original_name' => $fileName,
            'disk' => $disk,
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => Storage::disk($disk)->size($path),
            'visibility' => 'public',
            "tipo" => "pagares"
        ]);
    }
}
