<?php

namespace Tests\Feature;

use App\Enums\DrawMethod;
use App\Enums\RoleName;
use App\Enums\TournamentStatus;
use App\Models\Player;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentDrawAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_can_view_draw_but_cannot_generate_or_reset_it(): void
    {
        $user = User::factory()->create();
        $tournament = $this->registrationTournament();
        $players = collect([Player::factory()->create(['user_id' => $user->id]), Player::factory()->create()]);
        $tournament->players()->attach($players->pluck('id')->all(), [
            'registered_by' => $user->id,
            'source' => 'manual',
            'registered_at' => now(),
        ]);

        $this->actingAs($user)->get(route('tournaments.draws.show', $tournament))->assertOk();
        $this->actingAs($user)->get(route('tournaments.draws.create', $tournament))->assertForbidden();
        $this->actingAs($user)->post(route('tournaments.draws.preview', $tournament), [
            'method' => DrawMethod::Random->value,
            'avoid_rematches' => '0',
        ])->assertForbidden();
        $this->actingAs($user)->delete(route('tournaments.draws.destroy', $tournament))->assertForbidden();
    }

    public function test_referee_can_manage_matches_but_cannot_change_the_draw(): void
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => RoleName::Referee->value],
            ['name' => RoleName::Referee->label()],
        );
        $referee = User::factory()->create();
        $referee->roles()->attach($role);
        $tournament = $this->registrationTournament();
        $tournament->officials()->attach($referee, ['assigned_by' => $tournament->created_by, 'role' => 'referee', 'is_active' => true, 'assigned_at' => now()]);

        $this->assertTrue($referee->can('manageMatches', $tournament));
        $this->assertFalse($referee->can('manageDraw', $tournament));
    }

    public function test_finished_tournament_draw_is_visible_but_cannot_be_deleted(): void
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament();
        $players = Player::factory()->count(2)->create();
        $tournament->players()->attach($players->pluck('id')->all(), [
            'registered_by' => $admin->id,
            'source' => 'manual',
            'registered_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('tournaments.draws.store', $tournament), [
            'method' => DrawMethod::Random->value,
            'avoid_rematches' => '0',
            'resolved_order' => $players->pluck('id')->all(),
        ])->assertRedirect();

        $tournament->update(['status' => TournamentStatus::Finished]);

        $this->actingAs($admin)->get(route('tournaments.draws.show', $tournament))
            ->assertOk()
            ->assertDontSee('Eliminar esta tanda');
        $this->actingAs($admin)->delete(route('tournaments.draws.destroy', $tournament))->assertForbidden();
        $this->assertDatabaseHas('tournament_draws', ['tournament_id' => $tournament->id]);
    }
}
