<?php

namespace App\Services\Api;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = User::query();

        $this->applyFilters($query, $filters);

        $sortBy = $filters['sort_by'] ?? 'id';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $query->orderBy($sortBy, $sortDir);

        $perPage = (int) data_get($filters, 'per_page', 10);

        return $query->paginate($perPage)->withQueryString();
    }

    public function find(User $user): User
    {
        return $user;
    }

    public function create(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    public function update(User $user, array $data): User
    {
        if (array_key_exists('password', $data)) {
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }
        }

        $user->fill($data);
        $user->save();

        return $user->refresh();
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function selectList(?string $search, int $limit = 20): Collection
    {
        $limit = max(1, min($limit, 100));

        return User::query()
            ->when($search, function (Builder $q) use ($search) {
                $s = trim($search);
                $q->where(function (Builder $qq) use ($s) {
                    $qq->where('name', 'like', "%{$s}%")
                        ->orWhere('username', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%");
                });
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'username', 'email']);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $query->where(function (Builder $q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('username', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if (!empty($filters['email'])) {
            $query->where('email', 'like', '%' . trim($filters['email']) . '%');
        }

        if (!empty($filters['username'])) {
            $query->where('username', 'like', '%' . trim($filters['username']) . '%');
        }

        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . trim($filters['name']) . '%');
        }
    }
}
