<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_manage_team_logo_roster_captain_and_status(): void
    {
        Storage::fake('public');
        $admin = $this->administrator();
        [$captain, $member] = Player::factory()->count(2)->create();

        $this->actingAs($admin)->post(route('teams.store'), [
            'name' => 'Ticos Elite',
            'description' => 'Equipo nacional competitivo.',
            'logo' => $this->fakeImage('first.png'),
            'is_active' => '1',
            'player_ids' => [$captain->id, $member->id],
            'captain_id' => $captain->id,
        ])->assertRedirect();

        $team = Team::query()->where('name', 'Ticos Elite')->firstOrFail();
        $firstLogo = $team->logo_path;
        Storage::disk('public')->assertExists($firstLogo);
        $this->assertDatabaseHas('player_team', ['team_id' => $team->id, 'player_id' => $captain->id, 'is_captain' => true]);

        $this->actingAs($admin)->put(route('teams.update', $team), [
            'name' => 'Ticos Champions',
            'description' => 'Nueva etapa competitiva.',
            'logo' => $this->fakeImage('second.png'),
            'is_active' => '1',
            'player_ids' => [$member->id],
            'captain_id' => $member->id,
        ])->assertRedirect(route('teams.show', $team));

        $team->refresh();
        Storage::disk('public')->assertMissing($firstLogo);
        Storage::disk('public')->assertExists($team->logo_path);
        $this->assertDatabaseMissing('player_team', ['team_id' => $team->id, 'player_id' => $captain->id]);
        $this->assertDatabaseHas('player_team', ['team_id' => $team->id, 'player_id' => $member->id, 'is_captain' => true]);

        $this->actingAs($admin)->patch(route('teams.status', $team))->assertRedirect();
        $this->assertFalse($team->refresh()->is_active);

        $lastLogo = $team->logo_path;
        $this->actingAs($admin)->delete(route('teams.destroy', $team))->assertRedirect(route('teams.index'));
        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
        Storage::disk('public')->assertMissing($lastLogo);
    }

    public function test_roster_changes_are_audited(): void
    {
        $admin = $this->administrator();
        $player = Player::factory()->create();

        $this->actingAs($admin)->post(route('teams.store'), [
            'name' => 'Audit Esports',
            'description' => null,
            'is_active' => '1',
            'player_ids' => [$player->id],
            'captain_id' => $player->id,
        ]);

        $team = Team::query()->where('name', 'Audit Esports')->firstOrFail();

        $this->assertTrue(AuditLog::query()
            ->where('auditable_type', $team->getMorphClass())
            ->where('auditable_id', $team->id)
            ->where('action', 'roster.updated')
            ->exists());
    }

    public function test_captain_must_belong_to_selected_roster_and_name_is_unique(): void
    {
        $admin = $this->administrator();
        $selected = Player::factory()->create();
        $outsider = Player::factory()->create();
        Team::factory()->create(['name' => 'Existing Team']);

        $this->actingAs($admin)->post(route('teams.store'), [
            'name' => 'Existing Team',
            'is_active' => '1',
            'player_ids' => [$selected->id],
            'captain_id' => $outsider->id,
        ])->assertSessionHasErrors(['name', 'captain_id']);
    }
}
