<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('matchpoint.admin.password');

        if (blank($password) && app()->isProduction()) {
            throw new RuntimeException('MATCHPOINT_ADMIN_PASSWORD es obligatorio en producción.');
        }

        $user = User::query()->updateOrCreate(
            ['email' => config('matchpoint.admin.email')],
            [
                'name' => config('matchpoint.admin.name'),
                'password' => Hash::make($password ?: 'ChangeMe!123'),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $administrator = Role::query()->where('slug', RoleName::Administrator->value)->firstOrFail();
        $user->roles()->syncWithoutDetaching([$administrator->getKey()]);
    }
}
