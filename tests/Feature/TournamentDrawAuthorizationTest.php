<?php

namespace Tests\Feature;

use App\Enums\DrawMethod;
use App\Enums\RoleName;
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
        $players = Player::factory()->count(2)->create();
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

        $this->assertTrue($referee->can('manageMatches', $tournament));
        $this->assertFalse($referee->can('manageDraw', $tournament));
    }
}
