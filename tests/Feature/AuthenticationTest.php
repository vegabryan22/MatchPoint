<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_is_available(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Vuelve a la arena');
    }

    public function test_active_user_can_authenticate_and_login_is_audited(): void
    {
        $user = User::factory()->create(['password' => 'Secret!1234']);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Secret!1234',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->refresh()->last_login_at);
        $this->assertTrue(AuditLog::query()->where('action', 'auth.login')->where('user_id', $user->id)->exists());
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        $user = User::factory()->create(['password' => 'Secret!1234', 'is_active' => false]);

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Secret!1234',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
