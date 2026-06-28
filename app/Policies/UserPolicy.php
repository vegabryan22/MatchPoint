<?php

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdministrator();
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->isAdministrator() || $actor->is($user);
    }

    public function create(User $actor): bool
    {
        return $actor->isAdministrator();
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->isAdministrator() || $actor->is($user);
    }

    public function delete(User $actor, User $user): bool
    {
        return $actor->isAdministrator() && ! $actor->is($user);
    }
}
