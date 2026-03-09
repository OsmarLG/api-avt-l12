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
        $query = Venta::query()->with(['comprador', 'predio', 'predio.zone', 'user', "proximaLetra"]);

        if (! empty($filters['person_id'])) {
            $query->where('person_id', $filters['person_id']);
        }

        if (! empty($filters['predio_id'])) {
            $query->where('predio_id', $filters['predio_id']);
        }

        if (! empty($filters['estado'])) {
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
        return $venta->load(['comprador', "comprador.phones", 'aval', "aval.phones", 'predio', "predio.zone", 'user', 'cancelledBy', "proximaLetra"]);
    }

    public function create(array $data, int $userId): Venta
    {
        return DB::transaction(function () use ($data, $userId) {
            $data['user_id'] = $userId;
            $venta = Venta::create($data);

            if ($venta->metodo_pago === 'meses') {
                $this->generateLetras($venta);
            }

            return $venta->load(['comprador', 'aval', 'predio', 'user']);
        });
    }

    public function update(Venta $venta, array $data): Venta
    {
        $venta->update($data);

        return $venta->load(['comprador', 'aval', 'predio', 'user', 'cancelledBy']);
    }

    public function cancel(Venta $venta, string $comment, int $userId): Venta
    {
        return DB::transaction(function () use ($venta, $comment, $userId) {
            $venta->update([
                'estado' => 'cancelado',
                'id_cancelo' => $userId,
                'comentario_cancelacion' => $comment,
            ]);

            // Optional: cancel pending installments?
            // $venta->letras()->where('estado', 'pendiente')->update(['estado' => 'cancelado']);

            return $venta;
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
