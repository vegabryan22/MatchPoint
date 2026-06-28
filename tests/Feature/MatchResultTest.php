<?php

namespace Tests\Feature;

use App\Enums\BestOf;
use App\Enums\BracketType;
use App\Enums\MatchSlot;
use App\Enums\MatchStatus;
use App\Enums\RoleName;
use App\Enums\TournamentStatus;
use App\Models\AuditLog;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Role;
use App\Models\Round;
use App\Models\Score;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchResultTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_records_bo1_result_and_advances_winner(): void
    {
        $admin = $this->administrator();
        [$match, $destination] = $this->pendingMatch(BestOf::One, $admin, true);

        $this->actingAs($admin)->get(route('matches.results.edit', $match))
            ->assertOk()
            ->assertSee('Registrar resultado')
            ->assertSee($match->best_of->label());

        $this->actingAs($admin)->post(route('matches.results.store', $match), [
            'games' => [['participant_a_score' => 3, 'participant_b_score' => 1]],
            'duration_minutes' => 18,
            'observations' => 'Partido validado por el árbitro.',
        ])->assertRedirect(route('tournaments.draws.show', $match->tournament));

        $match->refresh();
        $this->assertSame(MatchStatus::Completed, $match->status);
        $this->assertSame($match->participant_a_id, $match->winner_id);
        $this->assertSame(1080, $match->duration_seconds);
        $this->assertSame($admin->id, $match->completed_by);
        $this->assertNotNull($match->completed_at);
        $this->assertDatabaseHas('scores', [
            'match_id' => $match->id,
            'game_number' => 1,
            'participant_a_score' => 3,
            'participant_b_score' => 1,
            'winner_id' => $match->participant_a_id,
        ]);
        $this->assertSame($match->winner_id, $destination->refresh()->participant_a_id);
        $this->assertTrue(AuditLog::query()->where('action', 'match.result_recorded')->where('auditable_id', $match->id)->exists());
    }

    public function test_bo3_finishes_when_participant_reaches_two_wins(): void
    {
        $admin = $this->administrator();
        [$match] = $this->pendingMatch(BestOf::Three, $admin);

        $this->actingAs($admin)->post(route('matches.results.store', $match), [
            'games' => [
                ['participant_a_score' => 2, 'participant_b_score' => 0],
                ['participant_a_score' => 1, 'participant_b_score' => 3],
                ['participant_a_score' => 4, 'participant_b_score' => 2],
            ],
        ])->assertRedirect();

        $this->assertSame(3, Score::query()->where('match_id', $match->id)->count());
        $this->assertSame($match->participant_a_id, $match->refresh()->winner_id);
    }

    public function test_bo5_accepts_a_three_zero_series_with_blank_remaining_games(): void
    {
        $admin = $this->administrator();
        [$match] = $this->pendingMatch(BestOf::Five, $admin);

        $this->actingAs($admin)->post(route('matches.results.store', $match), [
            'games' => [
                ['participant_a_score' => 2, 'participant_b_score' => 0],
                ['participant_a_score' => 3, 'participant_b_score' => 1],
                ['participant_a_score' => 1, 'participant_b_score' => 0],
                ['participant_a_score' => null, 'participant_b_score' => null],
                ['participant_a_score' => null, 'participant_b_score' => null],
            ],
        ])->assertRedirect();

        $this->assertSame(3, $match->scores()->count());
        $this->assertSame($match->participant_a_id, $match->refresh()->winner_id);
    }

    public function test_result_rejects_ties_incomplete_series_and_extra_games(): void
    {
        $admin = $this->administrator();
        [$tieMatch] = $this->pendingMatch(BestOf::One, $admin);
        [$incompleteMatch] = $this->pendingMatch(BestOf::Three, $admin);
        [$extraMatch] = $this->pendingMatch(BestOf::Three, $admin);

        $this->actingAs($admin)->post(route('matches.results.store', $tieMatch), [
            'games' => [['participant_a_score' => 2, 'participant_b_score' => 2]],
        ])->assertSessionHasErrors('games');

        $this->actingAs($admin)->post(route('matches.results.store', $incompleteMatch), [
            'games' => [['participant_a_score' => 2, 'participant_b_score' => 1]],
        ])->assertSessionHasErrors('games');

        $this->actingAs($admin)->post(route('matches.results.store', $extraMatch), [
            'games' => [
                ['participant_a_score' => 2, 'participant_b_score' => 1],
                ['participant_a_score' => 3, 'participant_b_score' => 0],
                ['participant_a_score' => 1, 'participant_b_score' => 0],
            ],
        ])->assertSessionHasErrors('games');

        $this->assertSame(0, Score::query()->count());
    }

    public function test_result_requires_tournament_in_progress_and_two_participants(): void
    {
        $admin = $this->administrator();
        [$match] = $this->pendingMatch(BestOf::One, $admin);
        $match->tournament->update(['status' => TournamentStatus::Registration]);

        $this->actingAs($admin)->post(route('matches.results.store', $match), [
            'games' => [['participant_a_score' => 1, 'participant_b_score' => 0]],
        ])->assertSessionHasErrors('match');

        $match->tournament->update(['status' => TournamentStatus::InProgress]);
        $match->update(['participant_b_id' => null]);
        $this->actingAs($admin)->post(route('matches.results.store', $match), [
            'games' => [['participant_a_score' => 1, 'participant_b_score' => 0]],
        ])->assertSessionHasErrors('match');
    }

    public function test_referee_can_record_result_but_regular_user_cannot(): void
    {
        $admin = $this->administrator();
        [$match] = $this->pendingMatch(BestOf::One, $admin);
        $regularUser = User::factory()->create();
        $referee = User::factory()->create();
        $role = Role::query()->firstOrCreate(
            ['slug' => RoleName::Referee->value],
            ['name' => RoleName::Referee->label()],
        );
        $referee->roles()->attach($role);
        $payload = ['games' => [['participant_a_score' => 1, 'participant_b_score' => 0]]];

        $this->actingAs($regularUser)->post(route('matches.results.store', $match), $payload)->assertForbidden();
        $this->actingAs($referee)->post(route('matches.results.store', $match), $payload)->assertRedirect();

        $this->assertSame($referee->id, $match->refresh()->completed_by);
    }

    public function test_safe_correction_replaces_scores_and_recalculates_destination(): void
    {
        $admin = $this->administrator();
        [$match, $destination] = $this->pendingMatch(BestOf::One, $admin, true);
        $this->actingAs($admin)->post(route('matches.results.store', $match), [
            'games' => [['participant_a_score' => 2, 'participant_b_score' => 0]],
        ]);

        $this->actingAs($admin)->put(route('matches.results.update', $match), [
            'games' => [['participant_a_score' => 1, 'participant_b_score' => 3]],
            'observations' => 'Marcador corregido.',
        ])->assertRedirect();

        $match->refresh();
        $this->assertSame($match->participant_b_id, $match->winner_id);
        $this->assertSame($match->participant_b_id, $destination->refresh()->participant_a_id);
        $this->assertSame(1, $match->scores()->count());
        $this->assertTrue(AuditLog::query()->where('action', 'match.result_corrected')->where('auditable_id', $match->id)->exists());
    }

    public function test_correction_is_blocked_after_dependent_match_finishes(): void
    {
        $admin = $this->administrator();
        [$match, $destination] = $this->pendingMatch(BestOf::One, $admin, true);
        $this->actingAs($admin)->post(route('matches.results.store', $match), [
            'games' => [['participant_a_score' => 2, 'participant_b_score' => 0]],
        ]);
        $destination->update([
            'participant_b_id' => Player::factory()->create()->id,
            'winner_id' => $destination->participant_a_id,
            'status' => MatchStatus::Completed,
        ]);

        $this->actingAs($admin)->put(route('matches.results.update', $match), [
            'games' => [['participant_a_score' => 0, 'participant_b_score' => 1]],
        ])->assertSessionHasErrors('match');

        $this->assertSame($match->participant_a_id, $match->refresh()->winner_id);
    }

    public function test_ajax_validation_returns_structured_errors(): void
    {
        $admin = $this->administrator();
        [$match] = $this->pendingMatch(BestOf::One, $admin);

        $this->actingAs($admin)->postJson(route('matches.results.store', $match), [
            'games' => [['participant_a_score' => 4, 'participant_b_score' => 4]],
        ])->assertUnprocessable()->assertJsonValidationErrors('games');
    }

    /** @return array{0: GameMatch, 1: GameMatch|null} */
    private function pendingMatch(BestOf $bestOf, User $actor, bool $withDestination = false): array
    {
        $tournament = Tournament::factory()->create([
            'status' => TournamentStatus::InProgress,
            'best_of' => $bestOf,
        ]);
        $players = Player::factory()->count(2)->create();
        $tournament->players()->attach($players->pluck('id')->all(), [
            'registered_by' => $actor->id,
            'source' => 'manual',
            'registered_at' => now(),
        ]);
        $round = Round::factory()->create([
            'tournament_id' => $tournament->id,
            'number' => 1,
            'bracket' => BracketType::Main,
        ]);
        $destination = $withDestination ? GameMatch::factory()->create([
            'tournament_id' => $tournament->id,
            'round_id' => $round->id,
            'sequence' => 2,
            'best_of' => $bestOf,
        ]) : null;
        if ($destination !== null) {
            GameMatch::factory()->create([
                'tournament_id' => $tournament->id,
                'round_id' => $round->id,
                'sequence' => 3,
                'best_of' => $bestOf,
                'winner_next_match_id' => $destination->id,
                'winner_next_slot' => MatchSlot::B,
            ]);
        }
        $match = GameMatch::factory()->create([
            'tournament_id' => $tournament->id,
            'round_id' => $round->id,
            'sequence' => 1,
            'participant_a_id' => $players[0]->id,
            'participant_b_id' => $players[1]->id,
            'best_of' => $bestOf,
            'winner_next_match_id' => $destination?->id,
            'winner_next_slot' => $destination ? MatchSlot::A : null,
        ]);

        return [$match, $destination];
    }
}
