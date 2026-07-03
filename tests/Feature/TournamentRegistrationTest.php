<?php

namespace Tests\Feature;

use App\Enums\ParticipantType;
use App\Enums\TournamentStatus;
use App\Models\AuditLog;
use App\Models\Player;
use App\Models\Team;
use App\Models\TournamentDraw;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_register_and_remove_individual_player(): void
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament();
        $player = Player::factory()->create();

        $this->actingAs($admin)->post(route('tournaments.registrations.store', $tournament), [
            'participant_id' => $player->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('tournament_players', [
            'tournament_id' => $tournament->id,
            'player_id' => $player->id,
            'registered_by' => $admin->id,
            'source' => 'manual',
        ]);

        $this->actingAs($admin)->delete(route('tournaments.registrations.destroy', [$tournament, $player->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('tournament_players', [
            'tournament_id' => $tournament->id,
            'player_id' => $player->id,
        ]);
        $this->assertTrue(AuditLog::query()->where('action', 'registration.created')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'registration.removed')->exists());
    }

    public function test_team_tournament_registers_only_active_teams(): void
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament(ParticipantType::Team);
        $activeTeam = Team::factory()->create();
        $inactiveTeam = Team::factory()->inactive()->create();

        $this->actingAs($admin)->post(route('tournaments.registrations.store', $tournament), [
            'participant_id' => $activeTeam->id,
        ])->assertRedirect();
        $this->actingAs($admin)->post(route('tournaments.registrations.store', $tournament), [
            'participant_id' => $inactiveTeam->id,
        ])->assertSessionHasErrors('participant_id');

        $this->assertDatabaseHas('tournament_teams', ['tournament_id' => $tournament->id, 'team_id' => $activeTeam->id]);
        $this->assertDatabaseMissing('tournament_teams', ['tournament_id' => $tournament->id, 'team_id' => $inactiveTeam->id]);
    }

    public function test_duplicate_and_capacity_limit_are_rejected(): void
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament(attributes: ['max_participants' => 1]);
        [$first, $second] = Player::factory()->count(2)->create();

        $this->actingAs($admin)->post(route('tournaments.registrations.store', $tournament), ['participant_id' => $first->id]);
        $this->actingAs($admin)->post(route('tournaments.registrations.store', $tournament), ['participant_id' => $first->id])
            ->assertSessionHasErrors('participant_id');
        $this->actingAs($admin)->post(route('tournaments.registrations.store', $tournament), ['participant_id' => $second->id])
            ->assertSessionHasErrors('participant_id');

        $this->assertSame(1, $tournament->players()->count());
    }

    public function test_registration_requires_open_status_and_current_window(): void
    {
        $admin = $this->administrator();
        $player = Player::factory()->create();
        $draft = $this->registrationTournament(attributes: ['status' => TournamentStatus::Draft]);
        $future = $this->registrationTournament(attributes: ['registration_starts_at' => now()->addHour()]);

        $this->actingAs($admin)->post(route('tournaments.registrations.store', $draft), ['participant_id' => $player->id])
            ->assertSessionHasErrors('registration');
        $this->actingAs($admin)->post(route('tournaments.registrations.store', $future), ['participant_id' => $player->id])
            ->assertSessionHasErrors('registration');
    }

    public function test_manager_can_toggle_extraordinary_registrations_during_active_tournament(): void
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament(attributes: ['status' => TournamentStatus::InProgress]);
        TournamentDraw::factory()->create(['tournament_id' => $tournament->id]);
        $player = Player::factory()->create();

        $this->actingAs($admin)->post(route('tournaments.registrations.store', $tournament), ['participant_id' => $player->id])
            ->assertSessionHasErrors('registration');

        $this->actingAs($admin)->patch(route('tournaments.registrations.extraordinary', $tournament), ['enabled' => 1])
            ->assertRedirect()->assertSessionHas('success', 'Inscripciones extraordinarias habilitadas.');
        $this->actingAs($admin)->post(route('tournaments.registrations.store', $tournament), ['participant_id' => $player->id])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tournament_players', ['tournament_id' => $tournament->id, 'player_id' => $player->id]);
        $this->assertTrue($tournament->refresh()->extraordinary_registration_enabled);
        $this->assertTrue(AuditLog::query()->where('action', 'registration.extraordinary_toggled')->exists());

        $this->actingAs($admin)->get(route('tournaments.registrations.index', $tournament))
            ->assertOk()->assertSee('Periodo extraordinario activo')->assertSee('Cerrar extraordinarias');
    }
}
