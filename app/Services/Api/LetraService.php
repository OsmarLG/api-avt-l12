<?php

namespace App\Services\Api;

use App\Models\Letra;
use App\Models\LetraInteresDescuento;
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

    public function createDiscountForIntereses(Letra $letra, array $data)
    {
        LetraInteresDescuento::create([
            "letra_interes_id" => $letra->intereses->first()->id,
            "porcentaje" => $data['porcentaje'],
            "monto_descontado" => $data['monto_descontado'],
            "comentario" => $data['comentario'] ?? "",
            "estado" => $data['estado'] ?? 'activo',
        ]);

        $letra->calcularInteres();
        $letra->venta->calcularCache();
        return $letra->load(['venta', 'intereses']);
    }

    public function batchCreateDiscounts(array $discounts): array
    {
        $results = [];

        foreach ($discounts as $discount) {
            $letra = Letra::findOrFail($discount['letra_id']);
            $results[] = $this->createDiscountForIntereses($letra, [
                'porcentaje' => $discount['porcentaje'],
                'monto_descontado' => $discount['monto_descontado'],
                'comentario' => $discount['comentario'] ?? "",
                'estado' => $discount['estado'] ?? 'activo',
            ]);
        }

        return $results;
    }
}
