<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use App\Notifications\UserAccountCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_update_and_delete_user(): void
    {
        Notification::fake();
        $admin = $this->administrator();
        $playerRole = Role::query()->create(['name' => 'Jugador', 'slug' => RoleName::Player->value]);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Ada Player',
            'email' => 'ada@example.com',
            'password' => 'Strong!1234',
            'password_confirmation' => 'Strong!1234',
            'is_active' => '1',
            'roles' => [$playerRole->id],
        ])->assertRedirect();

        $user = User::query()->where('email', 'ada@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole(RoleName::Player));
        Notification::assertSentTo($user, UserAccountCreated::class);

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => 'Ada Champion',
            'email' => $user->email,
            'password' => null,
            'is_active' => '0',
            'roles' => [$playerRole->id],
        ])->assertRedirect(route('admin.users.show', $user));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Ada Champion', 'is_active' => false]);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $user))->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_administrator_cannot_delete_own_account(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertForbidden();
    }
}
