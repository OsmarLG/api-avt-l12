<?php

namespace App\Http\Controllers;

use App\Http\Requests\Imports\CommitPadronImportRequest;
use App\Http\Requests\Imports\PreviewPadronImportRequest;
use App\Models\ImportBatch;
use App\Models\ImportBatchRecord;
use App\Models\User;
use App\Models\Zone;
use App\Services\Imports\GeoJsonFeatureFinder;
use App\Services\Imports\PadronImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Interfaz web del importador de padrones: cargar el Excel, revisar la simulación,
 * aplicarla y — si hizo falta — revertirla.
 */
class PadronImportController extends Controller
{
    private const DIRECTORIO = 'imports';

    public function __construct(private readonly PadronImportService $service) {}

    /** Lotes de importación, del más reciente al más viejo. */
    public function index(): View
    {
        return view('imports.padron.index', [
            'lotes' => ImportBatch::with(['user', 'zone'])->latest()->paginate(20),
        ]);
    }

    /** Formulario de carga. */
    public function create(): View
    {
        $opciones = $this->service->opcionesPorDefecto();

        return view('imports.padron.create', [
            'opciones' => $opciones,
            'zonas' => Zone::orderBy('nombre')->get(),
            'usuarios' => User::orderBy('name')->get(['id', 'name', 'email']),
            // Vía el buscador, no con is_readable: en el repositorio el catastro viaja
            // comprimido y sólo existe el .gz junto a esta ruta.
            'geojsonExiste' => (new GeoJsonFeatureFinder($opciones['geojson_path']))->existe(),
        ]);
    }

    /** Guarda el archivo, corre la simulación y deja el lote listo para confirmarse. */
    public function preview(PreviewPadronImportRequest $request): RedirectResponse
    {
        $archivo = $request->file('archivo');
        $opciones = $this->service->normalizarOpciones($request->opciones());

        try {
            $analisis = $this->service->analizar($archivo->getRealPath(), $opciones);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['archivo' => 'No se pudo leer el archivo: '.$e->getMessage()]);
        }

        $lote = ImportBatch::create([
            'tipo' => 'padron',
            'estado' => ImportBatch::ESTADO_PREVISUALIZADO,
            'archivo_nombre' => $archivo->getClientOriginalName(),
            'archivo_hash' => hash_file('sha256', $archivo->getRealPath()),
            'zona_nombre' => $analisis['zona']['nombre'] ?? null,
            'zona_id' => $analisis['zona']['id'] ?? null,
            'user_id' => $request->user()?->id,
            'opciones' => $analisis['opciones'],
            'resumen' => $analisis['resumen'],
        ]);

        $archivo->storeAs(self::DIRECTORIO, $lote->uuid.'.xlsx');

        return redirect()->route('imports.padron.show', $lote);
    }

    /**
     * Reporte del lote. Mientras está en previsualización se vuelve a analizar el
     * archivo para que refleje el estado actual de la base, no el de hace una hora.
     */
    public function show(ImportBatch $lote): View
    {
        if ($lote->estado !== ImportBatch::ESTADO_PREVISUALIZADO) {
            return view('imports.padron.aplicado', [
                'lote' => $lote->load(['user', 'zone', 'revertidoPor']),
                'conteos' => $lote->conteoCreados(),
                'registros' => $lote->registros()->orderBy('fila')->orderBy('id')->paginate(200),
            ]);
        }

        $ruta = $this->rutaArchivo($lote);
        $error = null;
        $analisis = null;

        if ($ruta === null) {
            $error = 'Ya no está el archivo original de esta previsualización. Vuelve a cargarlo.';
        } else {
            try {
                $analisis = $this->service->analizar($ruta, $lote->opciones ?? []);
            } catch (Throwable $e) {
                $error = 'No se pudo analizar el archivo: '.$e->getMessage();
            }
        }

        return view('imports.padron.preview', [
            'lote' => $lote,
            'analisis' => $analisis,
            'error' => $error,
        ]);
    }

    /** Aplica la importación sobre el mismo lote previsualizado. */
    public function commit(CommitPadronImportRequest $request, ImportBatch $lote): RedirectResponse
    {
        $ruta = $this->rutaArchivo($lote);

        if ($ruta === null) {
            return back()->withErrors(['archivo' => 'Ya no está el archivo original. Vuelve a cargarlo.']);
        }

        try {
            $this->service->aplicar(
                $ruta,
                $lote->archivo_nombre,
                $lote->opciones ?? [],
                $request->user()?->id,
                $lote
            );
        } catch (Throwable $e) {
            return back()->withErrors(['aplicar' => $e->getMessage()]);
        }

        return redirect()
            ->route('imports.padron.show', $lote)
            ->with('exito', 'Importación aplicada. Abajo está todo lo que se cargó.');
    }

    /** Deshace un lote aplicado. */
    public function rollback(ImportBatch $lote): RedirectResponse
    {
        try {
            $borrados = $this->service->revertir($lote, request()->user()?->id);
        } catch (Throwable $e) {
            return back()->withErrors(['revertir' => $e->getMessage()]);
        }

        $detalle = collect($borrados)->map(fn ($n, $modelo) => "{$n} {$modelo}")->implode(', ');

        return redirect()
            ->route('imports.padron.show', $lote)
            ->with('exito', 'Importación revertida. Se eliminaron: '.($detalle ?: 'nada'));
    }

    /** Descarta una previsualización que nunca se aplicó. */
    public function destroy(ImportBatch $lote): RedirectResponse
    {
        if ($lote->estado !== ImportBatch::ESTADO_PREVISUALIZADO) {
            return back()->withErrors(['descartar' => 'Sólo se pueden descartar previsualizaciones.']);
        }

        Storage::delete(self::DIRECTORIO.'/'.$lote->uuid.'.xlsx');
        $lote->delete();

        return redirect()->route('imports.padron.index')->with('exito', 'Previsualización descartada.');
    }

    /** CSV con todo lo que dejó el lote en la base, id por id. */
    public function registros(ImportBatch $lote): StreamedResponse
    {
        $nombre = 'importacion-'.Str::limit($lote->uuid, 8, '').'-'.$lote->created_at->format('Ymd-Hi').'.csv';

        return response()->streamDownload(function () use ($lote) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM: para que Excel respete los acentos
            fputcsv($salida, ['fila_excel', 'modelo', 'id', 'accion', 'referencia', 'creado_en']);

            $lote->registros()
                ->orderBy('fila')
                ->orderBy('id')
                ->chunk(500, function ($registros) use ($salida) {
                    foreach ($registros as $registro) {
                        fputcsv($salida, [
                            $registro->fila,
                            class_basename($registro->model_type),
                            $registro->model_id,
                            $registro->accion,
                            $registro->referencia,
                            $registro->created_at?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** CSV del análisis fila por fila, para revisarlo antes de aplicar. */
    public function reporteAnalisis(ImportBatch $lote): StreamedResponse|RedirectResponse
    {
        $ruta = $this->rutaArchivo($lote);

        if ($ruta === null) {
            return back()->withErrors(['archivo' => 'Ya no está el archivo original de esta previsualización.']);
        }

        $analisis = $this->service->analizar($ruta, $lote->opciones ?? []);
        $nombre = 'analisis-'.Str::limit($lote->uuid, 8, '').'.csv';

        return response()->streamDownload(function () use ($analisis) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($salida, [
                'fila', 'estatus', 'estatus_legal', 'clave_catastral', 'lote', 'manzana',
                'folio', 'comprador', 'telefono', 'fecha_contratacion',
                'm2_excel', 'm2_catastro', 'poligono', 'costo_lote', 'anticipo',
                'mensualidades', 'pagare', 'letras_pagadas', 'pagado', 'saldo_excel', 'saldo_calculado',
                'letras_a_crear', 'accion_predio', 'accion_persona', 'avisos', 'errores',
            ]);

            foreach ($analisis['filas'] as $fila) {
                fputcsv($salida, [
                    $fila['fila'],
                    $fila['estatus'],
                    $fila['estatus_legal'],
                    $fila['clave_catastral_excel'],
                    $fila['lote'],
                    $fila['manzana'],
                    $fila['folio'],
                    $fila['comprador'],
                    $fila['telefono'],
                    $fila['fecha_contratacion'],
                    $fila['m2_excel'],
                    $fila['m2_geojson'],
                    $fila['tiene_poligono'] ? 'sí' : 'no',
                    $fila['cantidad_total'],
                    $fila['anticipo'],
                    $fila['mensualidades'],
                    $fila['pagare'],
                    $fila['letras_pagadas'],
                    $fila['cantidad_pagada'],
                    $fila['saldo'],
                    $fila['saldo_calculado'] ?? '',
                    $fila['letras_a_generar'] ?? 0,
                    $fila['acciones']['predio'] ?? '',
                    $fila['acciones']['persona'] ?? '',
                    implode(' | ', $fila['avisos']),
                    implode(' | ', $fila['errores']),
                ]);
            }

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Detalle de un modelo concreto creado por el lote, para auditar sin salir de la vista. */
    public function registro(Request $request, ImportBatch $lote, ImportBatchRecord $registro): View
    {
        abort_unless($registro->import_batch_id === $lote->id, 404);

        return view('imports.padron.registro', [
            'lote' => $lote,
            'registro' => $registro,
            'modelo' => $registro->model_type::find($registro->model_id),
        ]);
    }

    private function rutaArchivo(ImportBatch $lote): ?string
    {
        $relativa = self::DIRECTORIO.'/'.$lote->uuid.'.xlsx';

        return Storage::exists($relativa) ? Storage::path($relativa) : null;
    }
}
