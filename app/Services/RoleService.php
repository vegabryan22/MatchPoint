<?php

namespace App\Services;

use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class RoleService
{
    public function __construct(private readonly RoleRepositoryInterface $roles) {}

    public function all(): Collection
    {
        return $this->roles->all();
    }
}
