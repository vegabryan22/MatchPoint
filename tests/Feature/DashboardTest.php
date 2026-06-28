<?php

namespace Tests\Feature;

use App\Enums\MatchStatus;
use App\Enums\ParticipantType;
use App\Enums\TournamentStatus;
use App\Models\AuditLog;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Round;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentChampion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_real_metrics_matches_results_and_champions(): void
    {
        $admin = $this->administrator();
        $tournament = Tournament::factory()->create(['status' => TournamentStatus::InProgress]);
        $round = Round::factory()->create(['tournament_id' => $tournament->id]);
        $players = Player::factory()->count(2)->create(['is_active' => true]);
        Player::factory()->create(['is_active' => false]);
        Team::factory()->create(['is_active' => true]);
        $pending = GameMatch::factory()->create([
            'tournament_id' => $tournament->id,
            'round_id' => $round->id,
            'sequence' => 1,
            'participant_a_id' => $players[0]->id,
            'participant_b_id' => $players[1]->id,
            'status' => MatchStatus::Pending,
            'scheduled_at' => now()->addHour(),
        ]);
        $completed = GameMatch::factory()->create([
            'tournament_id' => $tournament->id,
            'round_id' => $round->id,
            'sequence' => 2,
            'participant_a_id' => $players[0]->id,
            'participant_b_id' => $players[1]->id,
            'winner_id' => $players[0]->id,
            'status' => MatchStatus::Completed,
            'completed_at' => now(),
        ]);
        $completed->scores()->create([
            'game_number' => 1,
            'participant_a_score' => 4,
            'participant_b_score' => 2,
            'winner_id' => $players[0]->id,
        ]);
        TournamentChampion::factory()->create([
            'tournament_id' => $tournament->id,
            'participant_id' => $players[0]->id,
            'deciding_match_id' => $completed->id,
        ]);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-dashboard-metric="players"', false)
            ->assertSee($players[0]->nickname)
            ->assertSee($players[1]->nickname)
            ->assertSee('4 — 2')
            ->assertSee('Últimos campeones');

        $this->actingAs($admin)->getJson(route('dashboard.data'))
            ->assertOk()
            ->assertJsonPath('metrics.players', 3)
            ->assertJsonPath('metrics.active_players', 2)
            ->assertJsonPath('metrics.teams', 1)
            ->assertJsonPath('metrics.active_teams', 1)
            ->assertJsonPath('metrics.tournaments', 1)
            ->assertJsonPath('metrics.pending_matches', 1)
            ->assertJsonPath('metrics.completed_matches', 1)
            ->assertJsonPath('metrics.total_goals', 6)
            ->assertJsonStructure(['live_html', 'generated_at']);
    }

    public function test_dashboard_filters_by_mode_tournament_and_dates(): void
    {
        $admin = $this->administrator();
        $individualTournament = Tournament::factory()->create(['participant_type' => ParticipantType::Individual]);
        $teamTournament = Tournament::factory()->create(['participant_type' => ParticipantType::Team]);
        $individualRound = Round::factory()->create(['tournament_id' => $individualTournament->id]);
        $teamRound = Round::factory()->create(['tournament_id' => $teamTournament->id]);
        GameMatch::factory()->create([
            'tournament_id' => $individualTournament->id,
            'round_id' => $individualRound->id,
            'participant_type' => ParticipantType::Individual,
            'status' => MatchStatus::Completed,
            'completed_at' => now()->subMonth(),
        ]);
        GameMatch::factory()->create([
            'tournament_id' => $teamTournament->id,
            'round_id' => $teamRound->id,
            'participant_type' => ParticipantType::Team,
            'status' => MatchStatus::Completed,
            'completed_at' => now(),
        ]);

        $this->actingAs($admin)->getJson(route('dashboard.data', [
            'participant_type' => ParticipantType::Team->value,
            'tournament_id' => $teamTournament->id,
            'date_from' => now()->subDay()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))->assertOk()
            ->assertJsonPath('metrics.tournaments', 1)
            ->assertJsonPath('metrics.completed_matches', 1);
    }

    public function test_sensitive_audit_activity_is_only_visible_to_administrators(): void
    {
        $admin = $this->administrator();
        $user = User::factory()->create();
        AuditLog::factory()->create(['action' => 'sensitive.test.action', 'user_id' => $admin->id]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertDontSee('sensitive.test.action');
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('sensitive.test.action');
    }

    public function test_dashboard_resolves_team_participants_and_handles_empty_data(): void
    {
        $admin = $this->administrator();
        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('No hay partidos pendientes')
            ->assertSee('Aún no hay campeones');

        $tournament = Tournament::factory()->create([
            'status' => TournamentStatus::InProgress,
            'participant_type' => ParticipantType::Team,
        ]);
        $round = Round::factory()->create(['tournament_id' => $tournament->id]);
        $teams = Team::factory()->count(2)->create();
        GameMatch::factory()->create([
            'tournament_id' => $tournament->id,
            'round_id' => $round->id,
            'participant_type' => ParticipantType::Team,
            'participant_a_id' => $teams[0]->id,
            'participant_b_id' => $teams[1]->id,
            'status' => MatchStatus::Pending,
        ]);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee($teams[0]->name)->assertSee($teams[1]->name);
    }

    public function test_dashboard_validates_filters_and_limits_refresh_endpoint(): void
    {
        $admin = $this->administrator();
        $this->actingAs($admin)->get(route('dashboard', [
            'date_from' => '2026-06-20',
            'date_to' => '2026-06-10',
        ]))->assertSessionHasErrors('date_to');

        $route = Route::getRoutes()->getByName('dashboard.data');
        $this->assertContains('throttle:30,1', $route->gatherMiddleware());
    }
}
