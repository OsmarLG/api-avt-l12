<?php

namespace App\Services\Api;

use App\Models\Letra;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class VentaService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Venta::query()->with(['comprador', 'predio', 'predio.zone', 'user', 'proximaLetra', 'files']);

        if (!empty($filters['person_id'])) {
            $query->where('person_id', $filters['person_id']);
        }

        if (!empty($filters['predio_id'])) {
            $query->where('predio_id', $filters['predio_id']);
        }

        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
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
                        'saldo' => $letraData['saldo'],
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

            return $venta->load(['comprador', 'aval', 'predio', 'user', 'cancelledBy', 'files']);
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
}
