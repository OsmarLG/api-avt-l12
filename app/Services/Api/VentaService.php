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
        $query = Venta::query()->with(['comprador', 'aval', 'predio', 'user', 'cancelledBy']);

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
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();
    }

    public function find(Venta $venta): Venta
    {
        return $venta->load(['comprador', 'aval', 'predio', 'user', 'cancelledBy', 'letras.abonos']);
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

    public function ventasMonitorPaginated()
    {
        return DB::table('ventas')
            ->leftJoin('predios', 'predios.id', '=', 'ventas.predio_id')
            ->join('zones', 'predios.zona_id', '=', 'zones.id')
            ->join('people', 'people.id', '=', 'ventas.person_id')

            // letra pendiente (subquery)
            ->leftJoin('letras as l', function ($join) {
                $join->on('l.id', '=', DB::raw('(
                SELECT l2.id
                FROM letras l2
                WHERE l2.venta_id = ventas.id
                AND l2.estado = "pendiente"
                ORDER BY l2.fecha_vencimiento ASC
                LIMIT 1
            )'));
            })

            // abonos agrupados
            ->leftJoin(DB::raw('(
            SELECT 
                letra_id,
                SUM(monto) AS total_abonado
            FROM abonos
            GROUP BY letra_id
        ) as ab'), 'ab.letra_id', '=', 'l.id')

            ->select([
                'ventas.*',
                'predios.*',
                'zones.*',
                'people.*',

                'l.id as letra_id',
                'l.fecha_vencimiento',
                'l.monto as monto_letra',

                DB::raw('IFNULL(ab.total_abonado, 0) as total_abonado'),
                DB::raw('(l.monto - IFNULL(ab.total_abonado, 0)) as saldo_letra'),
            ])
            ->orderByDesc('ventas.id')
            ->paginate(10);
    }
}
