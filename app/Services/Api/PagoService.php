<?php

namespace App\Services\Api;

use App\Models\Abono;
use App\Models\Letra;
use App\Models\Pago;
use App\Models\Venta;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PagoService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = $this->applyFilters(Pago::query()->with(['person', 'user', 'cancelledBy']), $filters);

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        // Get the last pago_id from filtered results
        $lastPagoId = $this->applyFilters(Pago::query(), $filters)
            ->orderBy("id", "desc")
            ->where("estado", "activo")
            ->value('id');

        $paginator = $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        $paginator->last_pago_id = $lastPagoId;

        return $paginator;
    }

    private function applyFilters($query, array $filters)
    {
        if (! empty($filters['person_id'])) {
            $query->where('person_id', $filters['person_id']);
        }

        if (!empty($filters['pago_id'])) {
            $query->where('id', $filters['pago_id']);
        }

        if (!empty($filters['venta_id'])) {
            $query->whereHas('abonos.letra', function ($q) use ($filters) {
                $q->where('venta_id', $filters['venta_id']);
            });
        }

        if (!empty($filters['zone_id'])) {
            $query->whereHas('abonos.letra.venta.predio', function ($q) use ($filters) {
                $q->where('zona_id', $filters['zone_id']);
            });
        }

        if (!empty($filters['fecha_inicial'])) {
            $query->whereDate('created_at', '>=', $filters['fecha_inicial']);
        }

        if (!empty($filters['fecha_final'])) {
            $query->whereDate('created_at', '<=', $filters['fecha_final']);
        }


        return $query;
    }

    public function filterForPagosDuenos(array $filters)
    {
        $query = Pago::query()->with(['ticket']);
        $this->applyFilters($query, $filters);
        $queryPagosDuenosPrimeraVez = clone $query;
        $queryPagosDuenosReimpresion = clone $query;

        $queryPagosDuenosPrimeraVez
            ->whereNull('fecha_pago_dueno')
            ->get()
            ->each(function ($pago) {
                $pago->update([
                    'fecha_pago_dueno' => now(),
                    'folio_dueno' => 'PD-' . Str::upper(Str::random(4)),
                    'reimpresion_ticket_dueno' => false,
                ]);
            });

        $queryPagosDuenosReimpresion
            ->where("fecha_pago_dueno", "!=", null)
            ->update(['reimpresion_ticket_dueno' => true]);

        return $query->orderBy('id', 'desc')->get();
    }

    public function find(Pago $pago): Pago
    {
        return $pago->load(['person', 'user', 'cancelledBy', 'abonos.letra', 'ticket']);
    }

    public function create(array $data, int $userId): Pago
    {
        return DB::transaction(function () use ($data, $userId) {

            $monto = (float) $data["monto"];

            $venta = Venta::find($data["venta_id"]);
            $folio = 'P-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));

            $pago = Pago::create([
                'monto' => $data["monto"],
                'recibi' => $data["recibi"],
                'cambio' => $data["cambio"],
                'referenica' => $data["referenica"] ?? ($data["referencia"] ?? null),
                'person_id' => $venta->person_id,
                'folio' => $folio,
                'fecha_pago' => now(),
                'user_id' => $userId,
                'metodo_pago' => $data["metodo_pago"]
            ]);

            // distribuir el monto entre letras pendientes
            $restante = $monto;
            foreach ($venta->letrasPendientes() as $letra) {
                if ($restante <= 0) {
                    break;
                }

                $saldoLetra = $letra->montoRestante();
                if ($saldoLetra <= 0) {
                    continue;
                }

                $aplicado = min($saldoLetra, $restante);

                Abono::create([
                    'pago_id' => $pago->id,
                    'letra_id' => $letra->id,
                    'monto' => $aplicado,
                ]);

                $restante -= $aplicado;

                // actualizar saldo y estado de la letra según lo abonado
                $nuevoSaldo = max($saldoLetra - $aplicado, 0);
                $letra->saldo = $nuevoSaldo;
                if ($nuevoSaldo == 0) {
                    $letra->estado = 'pagado';
                } elseif ($nuevoSaldo < $letra->monto) {
                    $letra->estado = 'pendiente';
                }
                $letra->save();
            }

            // recalc cache de venta tras abonos
            $venta->calcularCache();

            $this->savePagoTicket($pago->id);

            return $pago->load("person", "user", "abonos.letra", "ticket");
        });
    }

    private function savePagoTicket(int $pagoId): void
    {
        $pago = Pago::query()
            ->with(['person', 'user', 'abonos.letra'])
            ->findOrFail($pagoId);
        $venta = null;
        $zona = null;
        if ($pago->abonos->isNotEmpty()) {
            $venta = $pago->abonos->first()->letra->venta;
            $venta->load([
                'comprador',
                'aval',
                'predio.zone',
                'proximaLetra'
            ]);
            $zona = $venta->predio->zone ?? null;
        }

        $ticketJson = json_encode([
            "zona" => $zona,
            'pago' => $pago->toArray(),
            "venta" => $venta?->toArray(),
            'abonos' => $pago->abonos->map(function (Abono $abono) {
                return [
                    'id' => $abono->id,
                    'monto' => $abono->monto,
                    'created_at' => optional($abono->created_at)->toISOString(),
                    'letra' => $abono->letra ? [
                        'id' => $abono->letra->id,
                        'venta_id' => $abono->letra->venta_id,
                        'consecutivo' => $abono->letra->consecutivo,
                        'descripcion' => $abono->letra->descripcion,
                        'tipo' => $abono->letra->tipo,
                        'monto' => $abono->letra->monto,
                        'saldo' => $abono->letra->saldo,
                        'estado' => $abono->letra->estado,
                        'fecha_vencimiento' => optional($abono->letra->fecha_vencimiento)->format('Y-m-d'),
                    ] : null,
                ];
            })->values()->all(),
        ], JSON_UNESCAPED_UNICODE);

        $exists = DB::table('pagos_tickets')->where('pago_id', $pago->id)->exists();

        if ($exists) {
            DB::table('pagos_tickets')
                ->where('pago_id', $pago->id)
                ->update([
                    'ticket' => $ticketJson,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('pagos_tickets')->insert([
            'pago_id' => $pago->id,
            'ticket' => $ticketJson,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function cancel(Pago $pago, string $comment, int $userId): Pago
    {
        return DB::transaction(function () use ($pago, $comment, $userId) {
            $pago->update([
                'estado' => 'cancelado',
                'id_cancelo' => $userId,
                'comentario_cancelacion' => $comment,
            ]);

            $pago->abonos()->update([
                'estado' => 'cancelado'
            ]);
            // Revert status of affected installments
            $ventaIds = [];
            foreach ($pago->abonos as $abono) {
                $this->updateLetraStatus($abono->letra_id);
                if ($abono->letra && $abono->letra->venta_id) {
                    $ventaIds[] = $abono->letra->venta_id;
                }
            }

            // Recalcular cache de las ventas afectadas por este pago
            foreach (array_unique($ventaIds) as $ventaId) {
                $venta = \App\Models\Venta::find($ventaId);
                if ($venta) {
                    $venta->calcularCache();
                }
            }

            return $pago;
        });
    }

    private function updateLetraStatus(int $letraId): void
    {
        $letra = Letra::find($letraId);
        if (! $letra) {
            return;
        }

        $totalAbonado = Abono::where('letra_id', $letraId)
            ->whereHas('pago', function ($q) {
                $q->where('estado', 'activo');
            })
            ->sum('monto');

        $letra->saldo = $letra->monto - $totalAbonado;
        if ($letra->saldo == 0) {
            $letra->estado = 'pagado';
        } elseif ($letra->saldo > 0) {
            $letra->estado = 'pendiente';
        } else {
            $letra->estado = 'pendiente';
        }

        $letra->save();

        // Check if all installments of the sale are paid
        $this->updateVentaStatus($letra->venta_id);
    }

    private function updateVentaStatus(int $ventaId): void
    {
        $venta = \App\Models\Venta::find($ventaId);
        if (! $venta || $venta->estado === 'cancelado') {
            return;
        }

        $allPaid = ! Letra::where('venta_id', $ventaId)
            ->where('estado', '!=', 'pagado')
            ->exists();

        if ($allPaid) {
            $venta->estado = 'pagado';
        } else {
            $venta->estado = 'pagando';
        }

        $venta->save();
    }
}
