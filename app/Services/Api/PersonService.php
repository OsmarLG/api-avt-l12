<?php

namespace App\Services\Api;

use App\Models\Email;
use App\Models\Person;
use App\Models\Phone;
use App\Models\Reference;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PersonService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Person::query()
            ->with(['phones', 'emails', 'references', 'files']);

        $this->applyFilters($query, $filters);

        $sortBy = $filters['sort_by'] ?? 'id';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $query->orderBy($sortBy, $sortDir);

        $perPage = (int) data_get($filters, 'per_page', 10);

        return $query->paginate($perPage)->withQueryString();
    }

    public function find(Person $person): Person
    {
        return $person->load(['phones', 'emails', 'references', 'files']);
    }

    public function create(array $data): Person
    {
        return DB::transaction(function () use ($data) {
            $relations = $this->extractRelations($data);

            $person = Person::create($data);

            if (!empty($relations['phones'])) {
                $person->phones()->createMany($relations['phones']); // morph: setea phoneable_*
            }

            if (!empty($relations['emails'])) {
                $person->emails()->createMany($relations['emails']); // morph
            }

            if (!empty($relations['references'])) {
                $person->references()->createMany($relations['references']); // hasMany: setea person_id
            }

            return $person->load(['phones', 'emails', 'references', 'files']);
        });
    }

    public function update(Person $person, array $data): Person
    {
        return DB::transaction(function () use ($person, $data) {
            $relations = $this->extractRelations($data);

            $person->fill($data);
            $person->save();

            // Sync Phones (morph)
            if (array_key_exists('phones', $relations)) {
                $this->syncMorphChildren(
                    parent: $person,
                    relationName: 'phones',
                    childModel: Phone::class,
                    payload: $relations['phones'] ?? []
                );
            }

            // Sync Emails (morph)
            if (array_key_exists('emails', $relations)) {
                $this->syncMorphChildren(
                    parent: $person,
                    relationName: 'emails',
                    childModel: Email::class,
                    payload: $relations['emails'] ?? []
                );
            }

            // Sync References (hasMany)
            if (array_key_exists('references', $relations)) {
                $this->syncHasManyReferences($person, $relations['references'] ?? []);
            }

            return $person->load(['phones', 'emails', 'references', 'files']);
        });
    }

    public function delete(Person $person): void
    {
        // Si se requiere limpiar relaciones al borrar.

        // $person->phones()->delete();
        // $person->emails()->delete();
        // $person->references()->delete();

        $person->delete(); // soft delete person
    }

    public function selectList(?string $search, int $limit = 20): Collection
    {
        $limit = max(1, min($limit, 100));

        return Person::query()
            ->when($search, function (Builder $q) use ($search) {
                $s = trim($search);
                $q->where(function (Builder $qq) use ($s) {
                    $qq->where('nombres', 'like', "%{$s}%")
                        ->orWhere('apellido_paterno', 'like', "%{$s}%")
                        ->orWhere('apellido_materno', 'like', "%{$s}%")
                        ->orWhere('curp', 'like', "%{$s}%")
                        ->orWhere('rfc', 'like', "%{$s}%")
                        ->orWhere('ine', 'like', "%{$s}%");
                });
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres')
            ->limit($limit)
            ->get(['id', 'nombres', 'apellido_paterno', 'apellido_materno', 'curp']);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $query->where(function (Builder $q) use ($s) {
                $q->where('nombres', 'like', "%{$s}%")
                    ->orWhere('apellido_paterno', 'like', "%{$s}%")
                    ->orWhere('apellido_materno', 'like', "%{$s}%")
                    ->orWhere('curp', 'like', "%{$s}%")
                    ->orWhere('rfc', 'like', "%{$s}%")
                    ->orWhere('ine', 'like', "%{$s}%");
            });
        }

        if (!empty($filters['curp'])) {
            $query->where('curp', 'like', '%' . trim($filters['curp']) . '%');
        }

        if (!empty($filters['rfc'])) {
            $query->where('rfc', 'like', '%' . trim($filters['rfc']) . '%');
        }

        if (!empty($filters['ine'])) {
            $query->where('ine', 'like', '%' . trim($filters['ine']) . '%');
        }

        if (!empty($filters['nombres'])) {
            $query->where('nombres', 'like', '%' . trim($filters['nombres']) . '%');
        }

        if (!empty($filters['apellido_paterno'])) {
            $query->where('apellido_paterno', 'like', '%' . trim($filters['apellido_paterno']) . '%');
        }

        if (!empty($filters['apellido_materno'])) {
            $query->where('apellido_materno', 'like', '%' . trim($filters['apellido_materno']) . '%');
        }
    }

    /**
     * Separar data principal de Person y data de relaciones.
     */
    private function extractRelations(array &$data): array
    {
        $relations = [];

        foreach (['phones', 'emails', 'references', 'files'] as $k) {
            if (array_key_exists($k, $data)) {
                $relations[$k] = $data[$k];
                unset($data[$k]);
            }
        }

        return $relations;
    }

    /**
     * Sync genérico para relaciones MorphMany.
     * - Actualiza por id si viene
     * - Crea si no viene id
     * - Elimina los que no vienen en el payload
     */
    private function syncMorphChildren(Person $parent, string $relationName, string $childModel, array $payload): void
    {
        /** @var \Illuminate\Database\Eloquent\Relations\MorphMany $rel */
        $rel = $parent->{$relationName}();

        $existing = $rel->get()->keyBy('id');
        $keepIds = [];

        foreach ($payload as $row) {
            $id = $row['id'] ?? null;

            if ($id && $existing->has($id)) {
                $existing[$id]->fill($row);
                $existing[$id]->save();
                $keepIds[] = $id;
            } else {
                $created = $rel->create($row);
                $keepIds[] = $created->id;
            }
        }

        // borrar los que ya no vienen
        $toDelete = $existing->keys()->diff($keepIds);
        if ($toDelete->isNotEmpty()) {
            $childModel::query()->whereIn('id', $toDelete)->delete();
        }
    }

    /**
     * Sync específico para referencias (HasMany).
     */
    private function syncHasManyReferences(Person $person, array $payload): void
    {
        $existing = $person->references()->get()->keyBy('id');
        $keepIds = [];

        foreach ($payload as $row) {
            $id = $row['id'] ?? null;

            if ($id && $existing->has($id)) {
                $existing[$id]->fill($row);
                $existing[$id]->save();
                $keepIds[] = $id;
            } else {
                $created = $person->references()->create($row);
                $keepIds[] = $created->id;
            }
        }

        $toDelete = $existing->keys()->diff($keepIds);
        if ($toDelete->isNotEmpty()) {
            Reference::query()->whereIn('id', $toDelete)->delete();
        }
    }

    public function createWithFiles(array $data, array $files, ?int $userId): Person
    {
        $uploadedPaths = [];
        $disk = 'public';

        try {
            return DB::transaction(function () use ($data, $files, $userId, &$uploadedPaths, $disk) {
                // 1. Create Person
                $person = $this->create($data);

                // 2. Upload Files
                $fileService = app(PersonFileService::class);

                foreach ($files as $fileData) {
                    $uploadedFile = $fileData['file'];

                    $meta = [
                        'title' => $fileData['title'] ?? null,
                        'visibility' => $fileData['visibility'] ?? 'private',
                        'disk' => $disk,
                    ];

                    $fileRecord = $fileService->upload($person, $uploadedFile, $meta, $userId);

                    if ($fileRecord->path) {
                        $uploadedPaths[] = $fileRecord->path;
                    }
                }

                return $person->load(['phones', 'emails', 'references', 'files']);
            });

        } catch (\Throwable $e) {
            // Manual Rollback of Physical Files
            if (!empty($uploadedPaths)) {
                foreach ($uploadedPaths as $path) {
                    // if (Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                    // }
                }
            }

            throw $e;
        }
    }
}
