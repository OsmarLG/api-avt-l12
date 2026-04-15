<?php

namespace App\Services\Api;

use App\Models\Letra;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LetraService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Letra::query()->with(['venta.comprador', 'abonos']);

        if (! empty($filters['venta_id'])) {
            $query->where('venta_id', $filters['venta_id']);
        }

        if (! empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        $sortBy = $filters['sort_by'] ?? 'fecha_vencimiento';
        $sortDir = $filters['sort_dir'] ?? 'asc';

        return $query->orderBy("id", $sortDir)
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();
    }

    public function find(Letra $letra): Letra
    {
        return $letra->load(['venta.comprador', 'abonos.pago']);
    }

    public function update(Letra $letra, array $data): Letra
    {
        $letra->update($data);

        return $letra->load(['venta', 'abonos']);
    }
}
