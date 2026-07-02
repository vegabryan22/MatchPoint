<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Player;
use App\Models\Role;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentStaffAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_assigns_transfers_and_removes_organizers(): void
    {
        $admin = $this->administrator();
        $role = Role::query()->firstOrCreate(['slug' => RoleName::Organizer->value], ['name' => RoleName::Organizer->label()]);
        $first = User::factory()->create();
        $second = User::factory()->create();
        $first->roles()->attach($role);
        $second->roles()->attach($role);
        $tournament = Tournament::factory()->create(['created_by' => $admin]);

        $this->actingAs($admin)->get(route('tournaments.staff.index', $tournament))
            ->assertOk()
            ->assertSee('Organizadores')
            ->assertSee('Árbitros');

        $this->actingAs($admin)->post(route('tournaments.staff.organizers.store', $tournament), ['user_id' => $first->id, 'is_primary' => 1])->assertRedirect();
        $this->actingAs($admin)->post(route('tournaments.staff.organizers.store', $tournament), ['user_id' => $second->id, 'is_primary' => 1])->assertRedirect();

        $this->assertTrue($first->can('view', $tournament));
        $this->assertTrue($second->can('update', $tournament));
        $this->assertFalse((bool) $tournament->organizers()->findOrFail($first->id)->pivot->is_primary);
        $this->assertTrue((bool) $tournament->organizers()->findOrFail($second->id)->pivot->is_primary);

        $this->actingAs($admin)->delete(route('tournaments.staff.organizers.destroy', [$tournament, $first]))->assertRedirect();
        $this->assertFalse($first->can('view', $tournament));
    }

    public function test_organizer_assigns_referee_only_to_owned_tournament(): void
    {
        $admin = $this->administrator();
        $organizerRole = Role::query()->firstOrCreate(['slug' => RoleName::Organizer->value], ['name' => RoleName::Organizer->label()]);
        $refereeRole = Role::query()->firstOrCreate(['slug' => RoleName::Referee->value], ['name' => RoleName::Referee->label()]);
        $organizer = User::factory()->create();
        $referee = User::factory()->create();
        $organizer->roles()->attach($organizerRole);
        $referee->roles()->attach($refereeRole);
        $owned = Tournament::factory()->create(['created_by' => $admin]);
        $foreign = Tournament::factory()->create(['created_by' => $admin]);
        $owned->organizers()->attach($organizer, ['assigned_by' => $admin->id, 'is_primary' => true, 'assigned_at' => now()]);

        $this->actingAs($organizer)->post(route('tournaments.staff.officials.store', $owned), ['user_id' => $referee->id])->assertRedirect();
        $this->actingAs($organizer)->post(route('tournaments.staff.officials.store', $foreign), ['user_id' => $referee->id])->assertForbidden();

        $this->assertTrue($referee->can('view', $owned));
        $this->assertTrue($referee->can('manageMatches', $owned));
        $this->assertFalse($referee->can('view', $foreign));
        $this->assertFalse($referee->can('manageDraw', $owned));
    }

    public function test_organizer_tournament_index_excludes_foreign_tournaments(): void
    {
        $admin = $this->administrator();
        $role = Role::query()->firstOrCreate(['slug' => RoleName::Organizer->value], ['name' => RoleName::Organizer->label()]);
        $organizer = User::factory()->create();
        $organizer->roles()->attach($role);
        $owned = Tournament::factory()->create(['name' => 'Torneo Propio', 'created_by' => $admin]);
        Tournament::factory()->create(['name' => 'Torneo Ajeno', 'created_by' => $admin]);
        $owned->organizers()->attach($organizer, ['assigned_by' => $admin->id, 'is_primary' => true, 'assigned_at' => now()]);

        $this->actingAs($organizer)->get(route('tournaments.index'))
            ->assertOk()->assertSee('Torneo Propio')->assertDontSee('Torneo Ajeno');
        $this->actingAs($organizer)->getJson(route('dashboard.data'))->assertOk()->assertJsonPath('metrics.tournaments', 1);
        $this->actingAs($organizer)->get(route('reports.index'))
            ->assertOk()->assertSee('Torneo Propio')->assertDontSee('Torneo Ajeno');
    }

    public function test_player_index_only_shows_tournaments_visible_to_organizer(): void
    {
        $admin = $this->administrator();
        $role = Role::query()->firstOrCreate(['slug' => RoleName::Organizer->value], ['name' => RoleName::Organizer->label()]);
        $organizer = User::factory()->create();
        $organizer->roles()->attach($role);
        $player = Player::factory()->create(['managed_by' => $organizer->id]);
        $owned = Tournament::factory()->create(['name' => 'Torneo Visible', 'created_by' => $admin]);
        $foreign = Tournament::factory()->create(['name' => 'Torneo Privado', 'created_by' => $admin]);
        $owned->organizers()->attach($organizer, ['assigned_by' => $admin->id, 'is_primary' => true, 'assigned_at' => now()]);
        $owned->players()->attach($player, ['source' => 'manual', 'registered_at' => now()]);
        $foreign->players()->attach($player, ['source' => 'manual', 'registered_at' => now()]);

        $this->actingAs($organizer)->get(route('players.index'))
            ->assertOk()
            ->assertSee('Torneo Visible')
            ->assertDontSee('Torneo Privado');
    }
}
