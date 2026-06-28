<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Models\User;
use App\Notifications\UserAccountCreated;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RoleRepositoryInterface $roles,
        private readonly AuditService $audit,
    ) {}

    public function paginate(?string $search): LengthAwarePaginator
    {
        return $this->users->paginate($search);
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $roleIds = Arr::pull($data, 'roles', []);
            $user = $this->users->create($data);

            if ($roleIds === []) {
                $roleIds = [$this->roles->findBySlug(RoleName::Guest->value)->getKey()];
            }

            $user->roles()->sync($roleIds);
            $this->audit->record('roles.updated', $user, [], ['role_ids' => $roleIds]);
            $user->notify(new UserAccountCreated);

            return $user->load('roles');
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $roleIds = Arr::pull($data, 'roles', null);

            if (($data['password'] ?? null) === null) {
                unset($data['password']);
            }

            $user = $this->users->update($user, $data);

            if (is_array($roleIds)) {
                $oldRoleIds = $user->roles()->pluck('roles.id')->all();
                $user->roles()->sync($roleIds);
                $this->audit->record('roles.updated', $user, ['role_ids' => $oldRoleIds], ['role_ids' => $roleIds]);
            }

            return $user->load('roles');
        });
    }

    public function delete(User $user, User $actor): void
    {
        abort_if($user->is($actor), 422, 'No puedes eliminar tu propia cuenta.');
        $this->users->delete($user);
    }
}
