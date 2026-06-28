<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Player;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_players_but_cannot_manage_them(): void
    {
        $user = User::factory()->create();
        $player = Player::factory()->create();

        $this->actingAs($user)->get(route('players.index'))->assertOk();
        $this->actingAs($user)->get(route('players.show', $player))->assertOk();
        $this->actingAs($user)->get(route('players.create'))->assertForbidden();
        $this->actingAs($user)->get(route('players.edit', $player))->assertForbidden();
        $this->actingAs($user)->delete(route('players.destroy', $player))->assertForbidden();
    }

    public function test_organizer_can_manage_players(): void
    {
        $organizer = User::factory()->create();
        $role = Role::query()->create([
            'name' => RoleName::Organizer->label(),
            'slug' => RoleName::Organizer->value,
        ]);
        $organizer->roles()->attach($role);
        $player = Player::factory()->create();

        $this->actingAs($organizer)->get(route('players.create'))->assertOk();
        $this->actingAs($organizer)->get(route('players.edit', $player))->assertOk();
    }
}
