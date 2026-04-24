<?php

namespace App\Services\Api;

use App\Http\Resources\Api\LetraResource;
use App\Models\Letra;
use App\Models\Person;
use App\Models\PredioObservacion;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

        $diasVencidos = $letrasVencidas->first()?->fecha_vencimiento->diffInDays(now());
        $primerVencimiento = $letrasVencidas->first()?->fecha_vencimiento;
        $interesPorDia = (float)$letrasVencidas->first()?->monto * ((float)$venta->intereses_porcentaje / 100) / 30;
        $interesPorMes = $interesPorDia * 30;

        $detalle = [];
        $detalle['intereses_activo'] = $venta->intereses_activo;
        $detalle['intereses_porcentaje'] = $venta->intereses_porcentaje;
        $detalle['intereses_dias_tregua'] = $venta->intereses_dias_tregua;
        $detalle['inicio'] = $venta->letrasVencidas->first()?->consecutivo;
        $detalle['fin'] = $venta->letrasVencidas->last()?->consecutivo;
        $detalle['dias_vencidos'] = round($diasVencidos, 2);
        $detalle['primer_vencimiento'] = $primerVencimiento;
        $detalle['mensualidad'] = $venta->letrasVencidas->first()?->monto;
        $detalle['interes_por_dia'] = round($interesPorDia, 2);
        $detalle['interes_por_mes'] = round($interesPorMes, 2);
        $detalle['total_intereses'] = $venta->getTotalIntereses();
        $detalle['total_pagar'] = $venta->letrasVencidas()->sum("saldo");
        $detalle['letras_vencidas'] = LetraResource::collection($venta->letrasVencidas()->with("intereses")->get());

        return $detalle;
    }
}
