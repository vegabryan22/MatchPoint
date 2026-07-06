<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\MatchStatus;
use App\Enums\ParticipantType;
use App\Enums\TournamentStatus;
use App\Models\AuditLog;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Team;
use App\Models\TournamentDraw;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

    public function test_manager_records_filters_and_audits_attendance(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-06 19:36:00', 'UTC'));
        $admin = $this->administrator();
        $tournament = $this->registrationTournament(attributes: ['status' => TournamentStatus::InProgress]);
        [$present, $absent] = Player::factory()->count(2)->create();
        $tournament->players()->attach([$present->id, $absent->id], [
            'registered_by' => $admin->id,
            'source' => 'manual',
            'registered_at' => now(),
        ]);

        $this->actingAs($admin)->patch(route('tournaments.registrations.attendance', [$tournament, $present->id]), [
            'attendance_status' => AttendanceStatus::Present->value,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($admin)->patch(route('tournaments.registrations.attendance', [$tournament, $absent->id]), [
            'attendance_status' => AttendanceStatus::Absent->value,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tournament_players', [
            'tournament_id' => $tournament->id,
            'player_id' => $present->id,
            'attendance_status' => AttendanceStatus::Present->value,
            'checked_in_by' => $admin->id,
        ]);
        $this->assertTrue(AuditLog::query()->where('action', 'registration.attendance_updated')->count() === 2);

        $this->actingAs($admin)->get(route('tournaments.registrations.index', [
            'tournament' => $tournament,
            'attendance' => AttendanceStatus::Present->value,
        ]))->assertOk()->assertSee($present->nickname)->assertSee('06/07/2026 13:36')->assertDontSee($absent->nickname);
    }

    public function test_finished_tournament_attendance_is_read_only(): void
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament(attributes: ['status' => TournamentStatus::Finished]);
        $player = Player::factory()->create();
        $tournament->players()->attach($player, [
            'registered_by' => $admin->id,
            'source' => 'manual',
            'registered_at' => now(),
            'attendance_status' => AttendanceStatus::Present->value,
            'checked_in_at' => now(),
            'checked_in_by' => $admin->id,
        ]);

        $this->actingAs($admin)->get(route('tournaments.registrations.index', $tournament))
            ->assertOk()->assertSee('Presente')->assertDontSee('Ausente</button>', false);
        $this->actingAs($admin)->patch(route('tournaments.registrations.attendance', [$tournament, $player->id]), [
            'attendance_status' => AttendanceStatus::Absent->value,
        ])->assertForbidden();

        $this->assertDatabaseHas('tournament_players', [
            'tournament_id' => $tournament->id,
            'player_id' => $player->id,
            'attendance_status' => AttendanceStatus::Present->value,
        ]);
    }

    public function test_historical_completed_matches_are_migrated_to_present_attendance(): void
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament(attributes: ['status' => TournamentStatus::Finished]);
        $players = Player::factory()->count(2)->create();
        $tournament->players()->attach($players->pluck('id'), [
            'registered_by' => $admin->id,
            'source' => 'manual',
            'registered_at' => now()->subDay(),
        ]);
        GameMatch::factory()->create([
            'tournament_id' => $tournament->id,
            'participant_a_id' => $players[0]->id,
            'participant_b_id' => $players[1]->id,
            'status' => MatchStatus::Completed,
            'completed_by' => $admin->id,
            'completed_at' => now()->subHour(),
        ]);

        $migration = require database_path('migrations/2026_07_05_000021_mark_existing_match_participants_present.php');
        $migration->up();

        $this->assertSame(2, $tournament->playerRegistrations()->where('attendance_status', AttendanceStatus::Present)->count());
        $this->assertSame(0, $tournament->playerRegistrations()->where('attendance_status', AttendanceStatus::Pending)->count());
    }

    public function test_finishing_tournament_marks_every_pending_registration_absent(): void
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament(attributes: ['status' => TournamentStatus::InProgress]);
        [$present, $pendingA, $pendingB] = Player::factory()->count(3)->create();
        $tournament->players()->attach($present, [
            'registered_by' => $admin->id,
            'source' => 'manual',
            'registered_at' => now(),
            'attendance_status' => AttendanceStatus::Present->value,
            'checked_in_at' => now(),
            'checked_in_by' => $admin->id,
        ]);
        $tournament->players()->attach([$pendingA->id, $pendingB->id], [
            'registered_by' => $admin->id,
            'source' => 'manual',
            'registered_at' => now(),
        ]);

        $this->actingAs($admin)->patch(route('tournaments.status', $tournament), [
            'status' => TournamentStatus::Finished->value,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(TournamentStatus::Finished, $tournament->refresh()->status);
        $this->assertDatabaseHas('tournament_players', [
            'tournament_id' => $tournament->id,
            'player_id' => $present->id,
            'attendance_status' => AttendanceStatus::Present->value,
        ]);
        $this->assertSame(2, $tournament->playerRegistrations()->where('attendance_status', AttendanceStatus::Absent)->count());
        $this->assertSame(2, AuditLog::query()->where('action', 'registration.attendance_auto_absent')->count());
    }

    public function test_existing_finished_tournament_pending_attendance_is_closed_by_migration(): void
    {
        $tournament = $this->registrationTournament(attributes: [
            'status' => TournamentStatus::Finished,
            'ends_at' => now()->subHour(),
        ]);
        $players = Player::factory()->count(2)->create();
        $tournament->players()->attach($players->pluck('id'), [
            'source' => 'manual',
            'registered_at' => now()->subDay(),
        ]);

        $migration = require database_path('migrations/2026_07_06_000022_close_pending_attendance_for_finished_tournaments.php');
        $migration->up();

        $this->assertSame(2, $tournament->playerRegistrations()->where('attendance_status', AttendanceStatus::Absent)->count());
        $this->assertSame(0, $tournament->playerRegistrations()->where('attendance_status', AttendanceStatus::Pending)->count());
    }
}
