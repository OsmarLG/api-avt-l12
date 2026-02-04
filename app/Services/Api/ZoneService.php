<?php

namespace App\Services\Api;

use App\Models\Zone;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ZoneService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Zone::query();

        $this->applyFilters($query, $filters);

        $sortBy = $filters['sort_by'] ?? 'id';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $query->orderBy($sortBy, $sortDir);

        $perPage = (int) data_get($filters, 'per_page', 10);

        return $query->paginate($perPage)->withQueryString();
    }

    public function create(array $data): Zone
    {
        return Zone::create($data);
    }

    public function update(Zone $zone, array $data): Zone
    {
        $zone->update($data);
        return $zone;
    }

    public function delete(Zone $zone): void
    {
        $zone->delete();
    }

    public function selectList(?string $search, int $limit = 20): Collection
    {
        $limit = max(1, min($limit, 100));

        return Zone::query()
            ->when($search, function (Builder $q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('dueno_nombre', 'like', "%{$search}%");
            })
            ->orderBy('nombre')
            ->limit($limit)
            ->get(['id', 'nombre', 'dueno_nombre']);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('dueno_nombre', 'like', "%{$search}%");
            });
        }
    }
}
