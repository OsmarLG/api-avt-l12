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
        $query = Pago::query()->with(['person', 'user', 'cancelledBy']);

        if (! empty($filters['person_id'])) {
            $query->where('person_id', $filters['person_id']);
        }

        if (!empty($filters['venta_id'])) {
            $query->whereHas('abonos.letra', function ($q) use ($filters) {
                $q->where('venta_id', $filters['venta_id']);
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query->orderBy($sortBy, $sortDir)
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();
    }

    public function find(Pago $pago): Pago
    {
        return $pago->load(['person', 'user', 'cancelledBy', 'abonos.letra']);
    }

    public function create(array $data, int $userId): Pago
    {
        return DB::transaction(function () use ($data, $userId) {

            $monto = (float) $data["monto"];

            $venta = Venta::find($data["venta_id"]);
            $folio = 'P-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));

            $pago = Pago::create([
                'monto' => $data["monto"],
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

            return $pago;
        });
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
            foreach ($pago->abonos as $abono) {
                $this->updateLetraStatus($abono->letra_id);
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

        if ($totalAbonado >= $letra->monto) {
            $letra->estado = 'pagado';
        } elseif ($totalAbonado > 0) {
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
