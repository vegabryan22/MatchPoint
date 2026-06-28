<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class EloquentRoleRepository implements RoleRepositoryInterface
{
    public function all(): Collection
    {
        return Role::query()->orderBy('name')->get();
    }

    public function findBySlug(string $slug): Role
    {
        return Role::query()->where('slug', $slug)->firstOrFail();
    }
}
