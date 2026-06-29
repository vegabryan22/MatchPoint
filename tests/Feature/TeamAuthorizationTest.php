<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_cannot_view_or_manage_unrelated_teams(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $this->actingAs($user)->get(route('teams.index'))->assertOk();
        $this->actingAs($user)->get(route('teams.show', $team))->assertForbidden();
        $this->actingAs($user)->get(route('teams.create'))->assertForbidden();
        $this->actingAs($user)->get(route('teams.edit', $team))->assertForbidden();
        $this->actingAs($user)->delete(route('teams.destroy', $team))->assertForbidden();
    }

    public function test_organizer_can_manage_teams(): void
    {
        $organizer = User::factory()->create();
        $role = Role::query()->create([
            'name' => RoleName::Organizer->label(),
            'slug' => RoleName::Organizer->value,
        ]);
        $organizer->roles()->attach($role);
        $team = Team::factory()->create(['managed_by' => $organizer->id]);

        $this->actingAs($organizer)->get(route('teams.create'))->assertOk();
        $this->actingAs($organizer)->get(route('teams.edit', $team))->assertOk();
    }
}
