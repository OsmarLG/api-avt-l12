<?php

namespace App\Services\Api;

use App\Models\File;
use App\Models\Venta;
use App\Support\NumberToWords;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VentaDocumentService
{
    /**
     * Genera el contrato de promesa de venta para una venta.
     */
    public function generateContract(Venta $venta): File
    {
        $venta->load(['comprador', 'aval', 'predio', 'predio.zone', 'letras' => fn($q) => $q->orderBy('consecutivo')]);

        $pdf = Pdf::loadView('pdfs.contract', [
            'venta' => $venta,
            'fecha' => now()->locale('es'),
            'monto_letras' => NumberToWords::convert($venta->costo_lote),
            'enganche_letras' => NumberToWords::convert($venta->enganche),
            'saldo_letras' => NumberToWords::convert($venta->costo_lote - $venta->enganche),
            'cuota_letras' => $venta->letras->where('tipo', 'letra')->first() ? NumberToWords::convert($venta->letras->where('tipo', 'letra')->first()->monto) : '',
            'dia_letras' => NumberToWords::convertToSpanishWords(now()->format('d')),
            'ano_letras' => NumberToWords::convertToSpanishWords(now()->format('Y')),
            'meses_a_pagar_letras' => NumberToWords::convertToSpanishWords($venta->meses_a_pagar),
        ]);

        $fileName = "Contrato_{$venta->folio}_" . Str::slug($venta->comprador->full_name ?? 'venta') . ".pdf";
        $path = "ventas/{$venta->id}/{$fileName}";
        $disk = 'public';

        Storage::disk($disk)->put($path, $pdf->output());

        return $venta->files()->create([
            'user_id' => $venta->user_id,
            'title' => 'Contrato de Promesa de Venta',
            'original_name' => $fileName,
            'disk' => $disk,
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => Storage::disk($disk)->size($path),
            'visibility' => 'public',
            "tipo" => "contrato"
        ]);
    }

    /**
     * Genera el recibo de anticipo para una venta.
     */
    public function generateReceipt(Venta $venta): File
    {
        $venta->load(['comprador', 'predio', 'predio.zone']);

        $pdf = Pdf::loadView('pdfs.receipt', [
            'venta' => $venta,
            'fecha' => now()->locale('es'),
            'monto_letras' => NumberToWords::convert($venta->costo_lote),
            'enganche_letras' => NumberToWords::convert($venta->enganche),
            'saldo_letras' => NumberToWords::convert($venta->costo_lote - $venta->enganche),
            'cuota_letras' => $venta->letras->where('tipo', 'letra')->first() ? NumberToWords::convert($venta->letras->where('tipo', 'letra')->first()->monto) : '',
        ]);

        $fileName = "Recibo_Anticipo_{$venta->folio}_" . Str::slug($venta->comprador->full_name ?? 'venta') . ".pdf";
        $path = "ventas/{$venta->id}/{$fileName}";
        $disk = 'public';

        Storage::disk($disk)->put($path, $pdf->output());

        return $venta->files()->create([
            'user_id' => $venta->user_id,
            'title' => 'Recibo de Anticipo',
            'original_name' => $fileName,
            'disk' => $disk,
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => Storage::disk($disk)->size($path),
            'visibility' => 'public',
            "tipo" => "anticipo",
        ]);
    }
}
