<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_but_not_manage_tournaments(): void
    {
        $user = User::factory()->create();
        $tournament = Tournament::factory()->create();

        $this->actingAs($user)->get(route('tournaments.index'))->assertOk();
        $this->actingAs($user)->get(route('tournaments.show', $tournament))->assertOk();
        $this->actingAs($user)->get(route('tournaments.create'))->assertForbidden();
        $this->actingAs($user)->get(route('tournaments.edit', $tournament))->assertForbidden();
        $this->actingAs($user)->post(route('tournaments.duplicate', $tournament))->assertForbidden();
        $this->actingAs($user)->delete(route('tournaments.destroy', $tournament))->assertForbidden();
    }

    public function test_organizer_can_manage_tournaments(): void
    {
        $organizer = User::factory()->create();
        $role = Role::query()->create([
            'name' => RoleName::Organizer->label(),
            'slug' => RoleName::Organizer->value,
        ]);
        $organizer->roles()->attach($role);
        $tournament = Tournament::factory()->create();

        $this->actingAs($organizer)->get(route('tournaments.create'))->assertOk();
        $this->actingAs($organizer)->get(route('tournaments.edit', $tournament))->assertOk();
        $this->actingAs($organizer)->post(route('tournaments.duplicate', $tournament))->assertRedirect();
    }
}
