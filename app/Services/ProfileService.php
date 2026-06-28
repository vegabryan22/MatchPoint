<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class ProfileService
{
    public function update(User $user, array $data): User
    {
        if ($user->email !== $data['email']) {
            $data['email_verified_at'] = null;
        }

        $user->update($data);

        return $user->refresh();
    }

    public function updatePassword(User $user, string $password): void
    {
        $user->update(['password' => Hash::make($password)]);
    }
}
