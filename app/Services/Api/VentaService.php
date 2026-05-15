<?php

namespace App\Services\Api;

use App\Http\Resources\Api\LetraResource;
use App\Models\Abono;
use App\Models\Letra;
use App\Models\Person;
use App\Models\PredioObservacion;
use App\Models\Venta;
use App\Support\GoogleStaticMap;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class VentaService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Venta::query()->with(['comprador', 'comprador.phones', 'predio', 'predio.zone', 'user', 'proximaLetra', 'files']);

        if (! empty($filters['person_id'])) {
            $query->where('person_id', $filters['person_id']);
        }

        if (! empty($filters['predio_id'])) {
            $query->where('predio_id', $filters['predio_id']);
        }

        if (! empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        if (! empty($filters['comprador_nombre'])) {
            $query->whereHas('comprador', function ($q) use ($filters) {
                $q->where('fullname', 'like', $filters['comprador_nombre'] . '%');
            });
        }

        $sortBy = $filters['sort_by'] ?? 'id';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query->orderBy($sortBy, $sortDir)
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();
    }

    public function find(Venta $venta): Venta
    {
        return $venta->load(['comprador', 'comprador.phones', 'aval', 'aval.phones', 'predio', 'predio.zone', 'user', 'cancelledBy', 'proximaLetra', 'files']);
    }

    public function create(array $data, int $userId): Venta
    {
        return DB::transaction(function () use ($data, $userId) {
            $data['user_id'] = $userId;

            if (isset($data['comprador_id'])) {
                $data['person_id'] = $data['comprador_id'];
            }

            $venta = Venta::create($data);

            if (isset($data['letras']) && is_array($data['letras'])) {
                foreach ($data['letras'] as $letraData) {
                    Letra::create([
                        'venta_id' => $venta->id,
                        'descripcion' => $letraData['descripcion'],
                        'monto' => $letraData['monto'],
                        'saldo' => $letraData['monto'],
                        'consecutivo' => $letraData['consecutivo'],
                        'tipo' => $letraData['tipo'],
                        'fecha_vencimiento' => \Carbon\Carbon::parse($letraData['fecha_expiracion']),
                        'estado' => 'pendiente',
                    ]);
                }
                $venta->calcularCache();
            } elseif ($venta->metodo_pago === 'meses') {
                $this->generateLetras($venta);
                $venta->calcularCache();
            }

            // Actualizar estado del predio
            $venta->predio->update(['estado' => 'pagando']);

            // Crear observación de venta creada
            $fechaVenta = now()->format('d/m/Y');
            PredioObservacion::create([
                'predio_id' => $venta->predio_id,
                'observacion' => "Venta creada el {$fechaVenta} con folio {$venta->folio}",
            ]);

            return $venta->load(['comprador', 'aval', 'predio', 'user', 'files']);
        });
    }

    public function update(Venta $venta, array $data): Venta
    {
        $venta->update($data);

        return $venta->load(['comprador', 'aval', 'predio', 'user', 'cancelledBy', 'files']);
    }

    public function cancel(Venta $venta, string $comment, int $userId): Venta
    {
        return DB::transaction(function () use ($venta, $comment, $userId) {
            $venta->update([
                'estado' => 'cancelado',
                'id_cancelo' => $userId,
                'comentario_cancelacion' => $comment,
            ]);

            // Liberar el predio
            $venta->predio->update(['estado' => 'disponible']);

            // Cancelar letras pendientes
            $venta->letras()->where('estado', 'pendiente')->update(['estado' => 'cancelado']);

            // Crear observación de cancelación
            $fechaCancelacion = now()->format('d/m/Y');
            PredioObservacion::create([
                'predio_id' => $venta->predio_id,
                'observacion' => "Venta con folio {$venta->folio} cancelada el {$fechaCancelacion}. Motivo: {$comment}",
            ]);

            return $venta->load(['comprador', 'aval', 'predio', 'user', 'cancelledBy', 'files']);
        });
    }

    public function cambiarComprador(Venta $venta, ?int $compradorId, ?int $avalId): Venta
    {
        return DB::transaction(function () use ($venta, $compradorId, $avalId) {
            /** @var Person|null $compradorAnterior */
            $compradorAnterior = $venta->comprador;
            /** @var Person|null $avalAnterior */
            $avalAnterior = $venta->aval;

            $updateData = [];

            if ($compradorId !== null) {
                $updateData['person_id'] = $compradorId;
            }

            if ($avalId !== null) {
                $updateData['aval_id'] = $avalId;
            }

            $venta->update($updateData);
            $ventaActualizada = $venta->fresh();

            if ($compradorId !== null) {
                /** @var Person $compradorNuevo */
                $compradorNuevo = $ventaActualizada->comprador;
                $nombreAnterior = $compradorAnterior?->fullname ?? 'Sin comprador';

                PredioObservacion::create([
                    'predio_id' => $venta->predio_id,
                    'observacion' => "Cambio de comprador de \"{$nombreAnterior}\" a \"{$compradorNuevo->fullname}\".",
                ]);
            }

            if ($avalId !== null) {
                /** @var Person $avalNuevo */
                $avalNuevo = $ventaActualizada->aval;
                $nombreAvalAnterior = $avalAnterior?->fullname ?? 'Sin aval';

                PredioObservacion::create([
                    'predio_id' => $venta->predio_id,
                    'observacion' => "Cambio de aval de \"{$nombreAvalAnterior}\" a \"{$avalNuevo->fullname}\".",
                ]);
            }

            return $ventaActualizada->load(['comprador', 'aval', 'predio', 'user', 'cancelledBy', 'files']);
        });
    }

    private function generateLetras(Venta $venta): void
    {
        $totalAPagar = $venta->costo_lote - $venta->enganche;
        $montoLetra = $totalAPagar / $venta->meses_a_pagar;
        $fecha = Carbon::parse($venta->fecha_primer_abono);

        for ($i = 1; $i <= $venta->meses_a_pagar; $i++) {
            Letra::create([
                'venta_id' => $venta->id,
                'descripcion' => "Letra {$i} de {$venta->meses_a_pagar}",
                'monto' => $montoLetra,
                'fecha_vencimiento' => $fecha->copy(),
                'estado' => 'pendiente',
            ]);
            $fecha->addMonth();
        }
    }

    public function detalleInteresMoratorio(Venta $venta): array
    {
        $letrasVencidas = $venta->letrasVencidas();

        $diasVencidos = (int) $letrasVencidas->first()?->fecha_vencimiento
        ->copy()
        ->startOfDay()
        ->diffInDays(now()->startOfDay(), false);
        $primerVencimiento = $letrasVencidas->first()?->fecha_vencimiento;
        $interesPorDia = (float)$letrasVencidas->first()?->getSaldoSinInteres() * ((float)$venta->intereses_porcentaje / 100) / 30;
        $interesPorMes = $interesPorDia * 30;

        $detalle = [];
        $detalle['intereses_activo'] = $venta->intereses_activo;
        $detalle['intereses_porcentaje'] = $venta->intereses_porcentaje;
        $detalle['intereses_dias_tregua'] = $venta->intereses_dias_tregua;
        $detalle['inicio'] = $venta->letrasVencidas->first()?->consecutivo;
        $detalle['fin'] = $venta->letrasVencidas->last()?->consecutivo;
        $detalle['dias_vencidos'] = round($diasVencidos, 2);
        $detalle['primer_vencimiento'] = $primerVencimiento;
        $detalle['mensualidad'] = $venta->letrasVencidas->first()?->getSaldoSinInteres() ?? 0;
        $detalle['interes_por_dia'] = round($interesPorDia, 2);
        $detalle['interes_por_mes'] = round($interesPorMes, 2);
        $detalle['total_intereses'] = $venta->getTotalIntereses();
        $detalle['total_pagar'] = $venta->letrasVencidas()->sum("saldo");
        $detalle['letras_vencidas'] = LetraResource::collection($venta->letrasVencidas()->with("intereses")->get());

        return $detalle;
    }

    public function changeMoratoriumParameters(Venta $venta, array $data): Venta
    {
        $venta->update($data);

        $venta->calcularIntereses();
        $venta->calcularCache();

        return $venta->fresh();
    }

    public function toggleIntereses(Venta $venta): Venta
    {
        if ($venta->intereses_activo) {
            $venta->update(['intereses_activo' => false]);
            $venta->letrasVencidas()->each(function (Letra $letra) {
                $letra->intereses()->update(['monto_neto' => 0, 'monto_bruto' => 0]);
                $letra->update(['saldo' => $letra->getSaldoSinInteres()]);
            });
        } else {
            $venta->update(['intereses_activo' => true]);
            $venta->calcularIntereses();
        }

        $venta->calcularCache();
        return $venta->fresh();
    }

    /**
     * Genera el PDF del estado de cuenta de una venta.
     */
    public function generateEstadoCuenta(Venta $venta): string
    {
        App::setLocale('es');
        Carbon::setLocale('es');

        $venta->calcularIntereses();
        $venta->refresh();

        $venta->load([
            'comprador',
            'aval',
            'predio.zone',
            'proximaLetra.intereses',
            'letras' => fn ($q) => $q->where('estado', '!=', 'cancelado')->orderBy('fecha_vencimiento'),
        ]);

        $predio = $venta->predio;
        $letraActual = $venta->proximaLetra;

        $totalLetras = $venta->letras->count();
        $letrasPagadas = $venta->letras->where('estado', 'pagado')->count();

        $totalPagado = (float) Abono::query()
            ->where('estado', 'activo')
            ->whereHas('pago', fn ($q) => $q->where('estado', 'activo'))
            ->whereHas('letra', fn ($q) => $q->where('venta_id', $venta->id))
            ->sum('monto');

        $saldoPendiente = (float) ($venta->saldo_venta ?? 0);
        $interesesAcumulados = (float) $venta->getTotalIntereses();
        $precioVenta = (float) $venta->costo_lote;

        $saldoSinInteresLetra = $letraActual ? (float) $letraActual->getSaldoSinInteres() : 0;
        $saldoConInteresLetra = $letraActual ? (float) ($letraActual->saldo ?? $saldoSinInteresLetra) : 0;
        $interesLetraActual = max(0, round($saldoConInteresLetra - $saldoSinInteresLetra, 2));

        $ultimoAbono = Abono::query()
            ->where('estado', 'activo')
            ->whereHas('pago', fn ($q) => $q->where('estado', 'activo'))
            ->whereHas('letra', fn ($q) => $q->where('venta_id', $venta->id))
            ->with('pago')
            ->latest('id')
            ->first();

        $compradorNombre = trim((string) ($venta->comprador->full_name ?? ''));
        if ($compradorNombre === '') {
            $compradorNombre = trim("{$venta->comprador->nombres} {$venta->comprador->apellido_paterno}");
        }

        $avalNombre = '—';
        if ($venta->aval) {
            $avalNombre = trim((string) ($venta->aval->full_name ?? ''));
            if ($avalNombre === '') {
                $avalNombre = trim("{$venta->aval->nombres} {$venta->aval->apellido_paterno}");
            }
        }

        $estadoLabel = match ($venta->estado) {
            'pagado' => 'PAGADA',
            'cancelado' => 'CANCELADA',
            default => 'ACTIVA',
        };

        $numeroLetraActual = $letraActual ? $this->numeroLetraDesdeDescripcion($letraActual) : '—';
        $progresoDisplay = $totalLetras > 0
            ? ($letraActual ? min($letrasPagadas, $totalLetras) : $letrasPagadas).' / '.$totalLetras
            : '—';

        $loteDisplay = $predio?->lote ?: ($predio?->gid ? 'L-'.$predio->gid : '—');
        $manzanaDisplay = $predio?->manzana ? (str_starts_with((string) $predio->manzana, 'MZ') ? $predio->manzana : 'MZ-'.$predio->manzana) : '—';

        $ultimoPagoFecha = null;
        $ultimoPagoMonto = null;
        if ($ultimoAbono?->pago) {
            $fecha = $ultimoAbono->pago->fecha_pago ?? $ultimoAbono->created_at;
            $ultimoPagoFecha = Carbon::parse($fecha)->locale('es')->translatedFormat('d \d\e F \d\e Y');
            $ultimoPagoMonto = (float) $ultimoAbono->monto;
        }

        $letrasVencidas = $venta->letrasVencidas()
            ->with('intereses')
            ->get()
            ->map(function (Letra $letra) {
                $saldoSinInteres = round((float) $letra->getSaldoSinInteres(), 2);
                $saldoConInteres = round((float) ($letra->saldo ?? $saldoSinInteres), 2);
                $interes = max(0, round($saldoConInteres - $saldoSinInteres, 2));

                return [
                    'consecutivo' => $letra->consecutivo ?? '—',
                    'descripcion' => $letra->descripcion ?? '—',
                    'saldo_sin_interes' => $saldoSinInteres,
                    'interes' => $interes,
                    'saldo_con_interes' => $saldoConInteres,
                    'fecha_vencimiento' => $letra->fecha_vencimiento
                        ->copy()
                        ->locale('es')
                        ->translatedFormat('d \d\e F \d\e Y'),
                ];
            })
            ->values();

        $data = [
            'fecha_emision' => Carbon::now()->locale('es')->translatedFormat('d \d\e F \d\e Y'),
            'empresa_nombre' => config('app.name', 'TU EMPRESA'),
            'folio' => $venta->folio ?: (string) $venta->id,
            'estado_label' => $estadoLabel,
            'estado_clase' => match ($venta->estado) {
                'cancelado' => 'badge-cancelada',
                'pagado' => 'badge-pagada',
                default => 'badge-activa',
            },
            'zona' => $predio?->zone?->nombre ?? '—',
            'comprador' => $compradorNombre,
            'aval' => $avalNombre,
            'clave_catastral' => $predio?->clave_catastral ?? '—',
            'manzana' => $manzanaDisplay,
            'lote' => $loteDisplay,
            'ubicacion' => $predio?->ubicacion ?? '—',
            'sup_terr' => $predio?->sup_terr ? number_format((float) $predio->sup_terr, 2).' m²' : '—',
            'precio_venta' => $precioVenta,
            'total_pagado' => $totalPagado,
            'saldo_pendiente' => $saldoPendiente,
            'intereses_acumulados' => $interesesAcumulados,
            'progreso' => $progresoDisplay,
            'numero_letra_actual' => $numeroLetraActual,
            'letra_sin_interes' => $saldoSinInteresLetra,
            'interes_letra_actual' => $interesLetraActual,
            'letra_con_interes' => $saldoConInteresLetra,
            'saldo_sin_interes' => $venta->getSaldoSinIntereses(),
            'saldo_con_interes' => $venta->saldo_venta,
            'ultimo_pago_fecha' => $ultimoPagoFecha,
            'ultimo_pago_monto' => $ultimoPagoMonto,
            'tiene_ultimo_pago' => $ultimoPagoFecha !== null,
            'resumen' => [
                'folio' => $venta->folio ?: (string) $venta->id,
                'zona' => $predio?->zone?->nombre ?? '—',
                'comprador' => $compradorNombre,
                'aval' => $avalNombre,
                'clave_catastral' => $predio?->clave_catastral ?? '—',
                'manzana' => $manzanaDisplay,
                'lote' => $loteDisplay,
                'letra_progreso' => $progresoDisplay,
                'total_pagado' => $totalPagado,
                'saldo_pendiente' => $saldoPendiente,
                'intereses_acumulados' => $interesesAcumulados,
                'precio_venta' => $precioVenta,
                'estado_label' => $estadoLabel,
                'ultimo_pago' => $ultimoPagoFecha
                    ? $ultimoPagoFecha.' · $'.number_format($ultimoPagoMonto, 2)
                    : '—',
            ],
            'observaciones' => $venta->fecha_primer_abono
                ? 'El pago de las letras se realiza conforme al calendario del contrato (primer abono: '
                    .$venta->fecha_primer_abono->locale('es')->translatedFormat('d \d\e F \d\e Y')
                    .'). En caso de retraso se generarán intereses moratorios conforme al contrato de compraventa.'
                : 'En caso de retraso en los pagos se generarán intereses moratorios conforme al contrato de compraventa.',
            'mapa_satellite_src' => GoogleStaticMap::satelliteImageForPredio($predio),
            'letras_vencidas' => $letrasVencidas,
            'logo_src' => $this->logoEstadoCuentaDataUri(),
        ];

        $pdf = Pdf::loadView('pdfs.venta_estado_cuenta', $data)->setPaper('letter', 'portrait');

        return $pdf->output();
    }

    private function logoEstadoCuentaDataUri(): ?string
    {
        $path = public_path('images/avt-logo.png');

        if (! is_readable($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
    }

    private function numeroLetraDesdeDescripcion(Letra $letra): string
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

        return (string) ($letra->consecutivo ?? '—');
    }
}
