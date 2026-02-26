<?php

namespace App\Services\Api;

use App\Models\Abono;
use App\Models\Letra;
use App\Models\Pago;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PagoService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Pago::query()->with(['person', 'user', 'cancelledBy']);

        if (! empty($filters['person_id'])) {
            $query->where('person_id', $filters['person_id']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query->orderBy($sortBy, $sortDir)
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();
    }

    public function find(Pago $pago): Pago
    {
        return $pago->load(['person', 'user', 'cancelledBy', 'abonos.letra']);
    }

    public function create(array $data, int $userId): Pago
    {
        return DB::transaction(function () use ($data, $userId) {
            $abonosData = $data['abonos'];
            unset($data['abonos']);

            $data['user_id'] = $userId;
            $pago = Pago::create($data);

            foreach ($abonosData as $abonoRow) {
                Abono::create([
                    'pago_id' => $pago->id,
                    'letra_id' => $abonoRow['letra_id'],
                    'monto' => $abonoRow['monto'],
                ]);

                $this->updateLetraStatus($abonoRow['letra_id']);
            }

            return $pago->load(['person', 'abonos']);
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
            $letra->estado = 'parcial';
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
