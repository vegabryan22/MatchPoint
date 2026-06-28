<?php

namespace Tests\Feature;

use App\Enums\GameType;
use App\Enums\MatchStatus;
use App\Enums\ParticipantType;
use App\Enums\TournamentStatus;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Round;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\StatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_ranking_calculates_results_goals_average_and_streak(): void
    {
        $tournament = Tournament::factory()->create(['status' => TournamentStatus::InProgress]);
        $round = Round::factory()->create(['tournament_id' => $tournament->id]);
        [$alpha, $beta, $gamma] = Player::factory()->count(3)->create();

        $this->completedMatch($tournament, $round, $alpha->id, $beta->id, $alpha->id, 3, 1, now()->subDays(3));
        $this->completedMatch($tournament, $round, $alpha->id, $gamma->id, $gamma->id, 0, 2, now()->subDays(2), 2);
        $this->completedMatch($tournament, $round, $alpha->id, $beta->id, $alpha->id, 4, 2, now()->subDay(), 3);

        $data = app(StatisticsService::class)->ranking(['participant_type' => ParticipantType::Individual->value]);
        $alphaStats = $data['ranking']->firstWhere('participant_id', $alpha->id);

        $this->assertSame(1, $alphaStats['rank']);
        $this->assertSame(3, $alphaStats['played']);
        $this->assertSame(2, $alphaStats['wins']);
        $this->assertSame(1, $alphaStats['losses']);
        $this->assertSame(7, $alphaStats['goals_for']);
        $this->assertSame(5, $alphaStats['goals_against']);
        $this->assertSame(2, $alphaStats['goal_difference']);
        $this->assertSame(2.33, $alphaStats['average']);
        $this->assertSame(66.7, $alphaStats['win_rate']);
        $this->assertSame('1V', $alphaStats['streak']);
    }

    public function test_ranking_assigns_the_same_position_when_metrics_are_equal(): void
    {
        $tournament = Tournament::factory()->create(['status' => TournamentStatus::InProgress]);
        $round = Round::factory()->create(['tournament_id' => $tournament->id]);
        $players = Player::factory()->count(4)->create();
        $this->completedMatch($tournament, $round, $players[0]->id, $players[1]->id, $players[0]->id, 2, 0, now()->subDay());
        $this->completedMatch($tournament, $round, $players[2]->id, $players[3]->id, $players[2]->id, 2, 0, now(), 2);

        $ranking = app(StatisticsService::class)->ranking([
            'participant_type' => ParticipantType::Individual->value,
        ])['ranking'];

        $this->assertSame(1, $ranking->firstWhere('participant_id', $players[0]->id)['rank']);
        $this->assertSame(1, $ranking->firstWhere('participant_id', $players[2]->id)['rank']);
    }

    public function test_statistics_filters_by_tournament_game_and_date(): void
    {
        $firstTournament = Tournament::factory()->create(['game' => GameType::EaSportsFc]);
        $secondTournament = Tournament::factory()->create(['game' => GameType::Pes]);
        $firstRound = Round::factory()->create(['tournament_id' => $firstTournament->id]);
        $secondRound = Round::factory()->create(['tournament_id' => $secondTournament->id]);
        $players = Player::factory()->count(2)->create();
        $this->completedMatch($firstTournament, $firstRound, $players[0]->id, $players[1]->id, $players[0]->id, 1, 0, now()->subMonth());
        $this->completedMatch($secondTournament, $secondRound, $players[0]->id, $players[1]->id, $players[1]->id, 0, 3, now());

        $data = app(StatisticsService::class)->ranking([
            'participant_type' => ParticipantType::Individual->value,
            'tournament_id' => $secondTournament->id,
            'game' => GameType::Pes->value,
            'date_from' => now()->subDay()->toDateString(),
        ]);

        $firstStats = $data['ranking']->firstWhere('participant_id', $players[0]->id);
        $this->assertSame(1, $firstStats['played']);
        $this->assertSame(0, $firstStats['wins']);
        $this->assertSame(3, $firstStats['goals_against']);
    }

    public function test_team_statistics_and_detail_history_are_available(): void
    {
        $admin = $this->administrator();
        $tournament = Tournament::factory()->create([
            'participant_type' => ParticipantType::Team,
            'status' => TournamentStatus::InProgress,
        ]);
        $round = Round::factory()->create(['tournament_id' => $tournament->id]);
        $teams = Team::factory()->count(2)->create();
        $this->completedMatch($tournament, $round, $teams[0]->id, $teams[1]->id, $teams[0]->id, 5, 2, now(), 1, ParticipantType::Team);

        $this->actingAs($admin)->get(route('statistics.index', ['participant_type' => ParticipantType::Team->value]))
            ->assertOk()
            ->assertSee($teams[0]->name);
        $this->actingAs($admin)->get(route('statistics.show', [ParticipantType::Team->value, $teams[0]->id]))
            ->assertOk()
            ->assertSee('Victoria')
            ->assertSee($teams[1]->name);
    }

    private function completedMatch(
        Tournament $tournament,
        Round $round,
        int $participantA,
        int $participantB,
        int $winner,
        int $scoreA,
        int $scoreB,
        mixed $completedAt,
        int $sequence = 1,
        ParticipantType $type = ParticipantType::Individual,
    ): GameMatch {
        $match = GameMatch::factory()->create([
            'tournament_id' => $tournament->id,
            'round_id' => $round->id,
            'sequence' => $sequence,
            'participant_type' => $type,
            'participant_a_id' => $participantA,
            'participant_b_id' => $participantB,
            'winner_id' => $winner,
            'status' => MatchStatus::Completed,
            'completed_at' => $completedAt,
        ]);
        $match->scores()->create([
            'game_number' => 1,
            'participant_a_score' => $scoreA,
            'participant_b_score' => $scoreB,
            'winner_id' => $winner,
        ]);

        return $match;
    }
}
