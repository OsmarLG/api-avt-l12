<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use App\Services\Api\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reportService)
    {
    }

    /**
     * Genera la Bitácora General de Pagos para todas las zonas.
     * 
     * @queryParam start_date date Periodo inicial (YYYY-MM-DD). Example: 2026-03-05
     * @queryParam end_date date Periodo final (YYYY-MM-DD). Example: 2026-03-05
     */
    public function bitacoraGeneral(Request $request): Response
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $pdfContent = $this->reportService->generateBitacoraGeneral(
            $request->start_date,
            $request->end_date
        );

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="bitacora_general.pdf"');
    }

    /**
     * Genera la Bitácora de Pagos para una zona específica.
     * 
     * @queryParam start_date date Periodo inicial (YYYY-MM-DD). Example: 2026-03-05
     * @queryParam end_date date Periodo final (YYYY-MM-DD). Example: 2026-03-05
     */
    public function bitacoraZona(Request $request, Zone $zone): Response
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $pdfContent = $this->reportService->generateBitacoraZona(
            $zone,
            $request->start_date,
            $request->end_date
        );

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="bitacora_zona_' . $zone->id . '.pdf"');
    }
}
