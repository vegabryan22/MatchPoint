<?php

namespace Tests\Feature;

use App\Enums\BracketType;
use App\Enums\DrawMethod;
use App\Enums\MatchStatus;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Events\MatchCompleted;
use App\Models\AuditLog;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentBracketTest extends TestCase
{
    use RefreshDatabase;

    public function test_bracket_renders_world_cup_layout_with_rounds_teams_and_scores(): void
    {
        [$admin, $tournament] = $this->generateBracket(TournamentFormat::SingleElimination, 8);
        $match = $tournament->rounds()->where('number', 1)->firstOrFail()->matches()->firstOrFail();
        $match->update(['winner_id' => $match->participant_a_id, 'status' => MatchStatus::Completed]);
        $match->scores()->create([
            'game_number' => 1,
            'participant_a_score' => 3,
            'participant_b_score' => 1,
            'winner_id' => $match->participant_a_id,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('tournaments.draws.show', $tournament))
            ->assertOk()
            ->assertSee('Llave estilo Copa del Mundo')
            ->assertSee('mp-world-bracket is-symmetric', false)
            ->assertSee('data-bracket-side="left"', false)
            ->assertSee('data-bracket-side="center"', false)
            ->assertSee('data-bracket-side="right"', false)
            ->assertSee('mp-world-cup', false)
            ->assertSee('Copa MatchPoint')
            ->assertSee('mp-world-team is-winner', false)
            ->assertSee('data-bracket-fullscreen', false)
            ->assertSee('3');

        $content = $response->getContent();

        $this->assertSame(2, substr_count($content, 'data-bracket-side="left"'));
        $this->assertSame(1, substr_count($content, 'data-bracket-side="center"'));
        $this->assertSame(2, substr_count($content, 'data-bracket-side="right"'));
        $this->assertSame(7, substr_count($content, '<article class="mp-world-match'));
    }

    public function test_single_elimination_generates_full_qualifying_and_empty_main_round(): void
    {
        [$admin, $tournament] = $this->generateBracket(TournamentFormat::SingleElimination, 8);

        $this->assertSame(3, $tournament->rounds()->count());
        $this->assertSame(7, $tournament->matches()->count());

        $firstRound = $tournament->rounds()->where('number', 1)->firstOrFail();
        $mainRound = $tournament->rounds()->where('number', 2)->firstOrFail();

        $this->assertSame(4, $firstRound->matches()->count());
        $this->assertTrue($firstRound->matches->every(fn (GameMatch $match): bool => $match->participant_a_id !== null && $match->participant_b_id !== null));
        $this->assertSame(2, $mainRound->matches()->count());
        $this->assertTrue($mainRound->matches->every(fn (GameMatch $match): bool => $match->participant_a_id === null && $match->participant_b_id === null));
    }

    public function test_projected_bracket_endpoint_reflects_results_recorded_from_another_session(): void
    {
        [$admin, $tournament] = $this->generateBracket(TournamentFormat::SingleElimination, 4);
        $viewer = User::factory()->create(['is_active' => true]);
        $tournament->players()->firstOrFail()->update(['user_id' => $viewer->id]);
        $tournament->update(['status' => TournamentStatus::InProgress]);
        $match = $tournament->rounds()->where('number', 1)->firstOrFail()->matches()->firstOrFail();
        $secondMatch = $tournament->rounds()->where('number', 1)->firstOrFail()->matches()->whereKeyNot($match->id)->firstOrFail();

        $before = $this->actingAs($viewer)->getJson(route('tournaments.draws.live', $tournament))
            ->assertOk()
            ->assertJsonStructure(['version', 'html'])
            ->json();

        $this->actingAs($admin)->post(route('matches.results.store', $match), [
            'games' => [['participant_a_score' => 3, 'participant_b_score' => 1]],
        ])->assertRedirect(route('tournaments.draws.show', $tournament));
        $this->actingAs($admin)->post(route('matches.results.store', $secondMatch), [
            'games' => [['participant_a_score' => 2, 'participant_b_score' => 0]],
        ])->assertRedirect(route('tournaments.draws.show', $tournament));

        $after = $this->actingAs($viewer)->getJson(route('tournaments.draws.live', $tournament))
            ->assertOk()
            ->json();

        $this->assertNotSame($before['version'], $after['version']);
        $this->assertStringContainsString('mp-world-team is-winner', $after['html']);
        $this->assertStringContainsString('>3</strong>', $after['html']);
        $mainMatch = $tournament->rounds()->where('number', 2)->firstOrFail()->matches()->firstOrFail();
        $this->assertContains($match->participant_a_id, [$mainMatch->participant_a_id, $mainMatch->participant_b_id]);
    }

    public function test_in_progress_bracket_renders_inline_forms_and_mobile_referee_mode(): void
    {
        [$admin, $tournament] = $this->generateBracket(TournamentFormat::SingleElimination, 4);
        $tournament->update(['status' => TournamentStatus::InProgress]);

        $response = $this->actingAs($admin)->get(route('tournaments.draws.show', $tournament))
            ->assertOk()
            ->assertSee('Modo árbitro')
            ->assertSee('Ingreso rápido de marcadores')
            ->assertSee('data-inline-result-form', false)
            ->assertSee('data-score-step="1"', false)
            ->assertSee('data-score-step="-1"', false);

        $this->assertSame(3, substr_count($response->getContent(), '<article class="mp-world-match'));
        $this->assertSame(2, substr_count($response->getContent(), '<article class="mp-mobile-match'));
    }

    public function test_single_elimination_with_48_players_uses_full_qualifying_round(): void
    {
        [, $tournament] = $this->generateBracket(TournamentFormat::SingleElimination, 48);

        $this->assertSame(6, $tournament->rounds()->count());
        $this->assertSame(55, $tournament->matches()->count());
        $this->assertSame(24, $tournament->rounds()->where('number', 1)->firstOrFail()->matches()->count());
        $this->assertSame(0, $tournament->matches()->where('status', MatchStatus::Bye)->count());
        $this->assertSame(16, $tournament->rounds()->where('number', 2)->firstOrFail()->matches()->count());
    }

    public function test_qualifying_round_selects_winners_and_best_loser(): void
    {
        [$admin, $tournament] = $this->generateBracket(TournamentFormat::SingleElimination, 6);

        $qualifyingMatches = $tournament->rounds()->where('number', 1)->firstOrFail()->matches()->get();
        $bestLoserId = $qualifyingMatches->first()->participant_b_id;

        foreach ($qualifyingMatches as $index => $match) {
            $participantAScore = $index === 0 ? 5 : 3;
            $participantBScore = $index === 0 ? 4 : 0;
            $match->scores()->create([
                'game_number' => 1,
                'participant_a_score' => $participantAScore,
                'participant_b_score' => $participantBScore,
                'winner_id' => $match->participant_a_id,
                'created_by' => $admin->id,
            ]);
            $match->update(['winner_id' => $match->participant_a_id, 'status' => MatchStatus::Completed]);
            MatchCompleted::dispatch($match->id, $admin->id);
        }

        $mainMatches = $tournament->rounds()->where('number', 2)->firstOrFail()->matches;
        $qualifiedIds = $mainMatches->flatMap(fn (GameMatch $match): array => [$match->participant_a_id, $match->participant_b_id]);

        $this->assertCount(3, $qualifyingMatches);
        $this->assertCount(2, $mainMatches);
        $this->assertContains($bestLoserId, $qualifiedIds);
        $this->assertTrue($mainMatches->every(fn (GameMatch $match): bool => $match->participant_a_id !== null && $match->participant_b_id !== null));
    }

    public function test_all_play_format_rejects_odd_participant_count(): void
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament(attributes: ['format' => TournamentFormat::SingleElimination]);
        $players = Player::factory()->count(15)->create();
        $tournament->players()->attach($players->pluck('id'), ['source' => 'manual', 'registered_at' => now()]);

        $this->actingAs($admin)->post(route('tournaments.draws.store', $tournament), [
            'method' => DrawMethod::Random->value,
            'avoid_rematches' => '0',
            'resolved_order' => $players->pluck('id')->all(),
        ])->assertSessionHasErrors('draw');

        $this->assertSame(0, $tournament->matches()->count());
    }

    public function test_completed_match_advances_winner_once_and_records_audit(): void
    {
        [$admin, $tournament] = $this->generateBracket(TournamentFormat::SingleElimination, 8);
        $this->completeQualificationRound($tournament, $admin);
        $match = $tournament->rounds()->where('number', 2)->firstOrFail()->matches()->firstOrFail();
        $match->update(['winner_id' => $match->participant_a_id, 'status' => MatchStatus::Completed]);

        MatchCompleted::dispatch($match->id, $admin->id);
        MatchCompleted::dispatch($match->id, $admin->id);

        $destination = GameMatch::query()->findOrFail($match->winner_next_match_id);
        $this->assertSame($match->participant_a_id, $destination->participant_a_id);
        $this->assertSame(1, AuditLog::query()->where('action', 'match.advanced')->where('auditable_id', $match->id)->count());
    }

    public function test_double_elimination_generates_winner_loser_and_final_brackets(): void
    {
        [$admin, $tournament] = $this->generateBracket(TournamentFormat::DoubleElimination, 8);

        $this->assertSame(9, $tournament->rounds()->count());
        $this->assertSame(15, $tournament->matches()->count());
        $this->assertSame(7, $tournament->matches()->whereHas('round', fn ($query) => $query->where('bracket', BracketType::Main))->count());
        $this->assertSame(6, $tournament->matches()->whereHas('round', fn ($query) => $query->where('bracket', BracketType::Losers))->count());
        $this->assertSame(2, $tournament->matches()->whereHas('round', fn ($query) => $query->where('bracket', BracketType::Finals))->count());

        $openingMatch = $tournament->rounds()
            ->where('bracket', BracketType::Main)
            ->where('number', 1)
            ->firstOrFail()
            ->matches()
            ->firstOrFail();
        $this->assertNotNull($openingMatch->winner_next_match_id);
        $this->assertNotNull($openingMatch->loser_next_match_id);
    }

    public function test_double_elimination_moves_winner_and_loser_to_their_destinations(): void
    {
        [$admin, $tournament] = $this->generateBracket(TournamentFormat::DoubleElimination, 4);
        $match = $tournament->rounds()
            ->where('bracket', BracketType::Main)
            ->where('number', 1)
            ->firstOrFail()
            ->matches()
            ->firstOrFail();
        $match->update(['winner_id' => $match->participant_b_id, 'status' => MatchStatus::Completed]);

        MatchCompleted::dispatch($match->id, $admin->id);

        $winnerDestination = GameMatch::query()->findOrFail($match->winner_next_match_id);
        $loserDestination = GameMatch::query()->findOrFail($match->loser_next_match_id);
        $this->assertSame($match->participant_b_id, $winnerDestination->participant_a_id);
        $this->assertSame($match->participant_a_id, $loserDestination->participant_a_id);
    }

    public function test_grand_final_activates_reset_only_when_loser_bracket_wins(): void
    {
        [$admin, $tournament] = $this->generateBracket(TournamentFormat::DoubleElimination, 2);
        $openingMatch = $tournament->rounds()
            ->where('bracket', BracketType::Main)
            ->firstOrFail()
            ->matches()
            ->firstOrFail();
        $grandFinal = $tournament->rounds()
            ->where('bracket', BracketType::Finals)
            ->where('number', 1)
            ->firstOrFail()
            ->matches()
            ->firstOrFail();
        $reset = $tournament->matches()->where('is_conditional', true)->firstOrFail();

        $openingMatch->update([
            'winner_id' => $openingMatch->participant_a_id,
            'status' => MatchStatus::Completed,
        ]);
        MatchCompleted::dispatch($openingMatch->id, $admin->id);

        $grandFinal->refresh();
        $grandFinal->update([
            'winner_id' => $grandFinal->participant_b_id,
            'status' => MatchStatus::Completed,
        ]);
        MatchCompleted::dispatch($grandFinal->id, $admin->id);

        $reset->refresh();
        $this->assertSame($grandFinal->participant_a_id, $reset->participant_a_id);
        $this->assertSame($grandFinal->participant_b_id, $reset->participant_b_id);
        $this->assertSame(MatchStatus::Pending, $reset->status);
    }

    public function test_grand_final_cancels_reset_when_winner_bracket_wins(): void
    {
        [$admin, $tournament] = $this->generateBracket(TournamentFormat::DoubleElimination, 2);
        $openingMatch = $tournament->rounds()
            ->where('bracket', BracketType::Main)
            ->firstOrFail()
            ->matches()
            ->firstOrFail();
        $openingMatch->update([
            'winner_id' => $openingMatch->participant_a_id,
            'status' => MatchStatus::Completed,
        ]);
        MatchCompleted::dispatch($openingMatch->id, $admin->id);

        $grandFinal = $tournament->rounds()
            ->where('bracket', BracketType::Finals)
            ->where('number', 1)
            ->firstOrFail()
            ->matches()
            ->firstOrFail();
        $grandFinal->update([
            'winner_id' => $grandFinal->participant_a_id,
            'status' => MatchStatus::Completed,
        ]);
        MatchCompleted::dispatch($grandFinal->id, $admin->id);

        $reset = $tournament->matches()->where('is_conditional', true)->firstOrFail();
        $this->assertSame(MatchStatus::Cancelled, $reset->status);
        $this->assertNull($reset->participant_a_id);
        $this->assertNull($reset->participant_b_id);
    }

    private function completeQualificationRound(Tournament $tournament, User $admin): void
    {
        $matches = $tournament->rounds()->where('number', 1)->firstOrFail()->matches;
        foreach ($matches as $match) {
            $match->update([
                'winner_id' => $match->participant_a_id,
                'status' => MatchStatus::Completed,
            ]);
            MatchCompleted::dispatch($match->id, $admin->id);
        }
    }

    /** @return array{0: User, 1: Tournament} */
    private function generateBracket(TournamentFormat $format, int $participantCount): array
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament(attributes: [
            'format' => $format,
            'max_participants' => max(4, $participantCount),
        ]);
        $players = Player::factory()->count($participantCount)->create();
        $tournament->players()->attach($players->pluck('id')->all(), [
            'registered_by' => $admin->id,
            'source' => 'manual',
            'registered_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('tournaments.draws.store', $tournament), [
            'method' => DrawMethod::Random->value,
            'avoid_rematches' => '0',
            'resolved_order' => $players->pluck('id')->all(),
        ])->assertRedirect(route('tournaments.draws.show', $tournament));

        return [$admin, $tournament->refresh()];
    }
}
