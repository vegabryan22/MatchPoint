<?php

namespace Tests\Feature;

use App\Enums\BestOf;
use App\Enums\BracketType;
use App\Enums\MatchStatus;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Models\AuditLog;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Round;
use App\Models\Tournament;
use App\Models\TournamentChampion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentChampionTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_elimination_final_crowns_and_displays_champion(): void
    {
        $admin = $this->administrator();
        [$tournament, $final, $players] = $this->finalMatch(TournamentFormat::SingleElimination, $admin->id);

        $this->actingAs($admin)->post(route('matches.results.store', $final), [
            'games' => [['participant_a_score' => 3, 'participant_b_score' => 1]],
        ])->assertRedirect();

        $this->assertDatabaseHas('tournament_champions', [
            'tournament_id' => $tournament->id,
            'participant_id' => $players[0]->id,
            'deciding_match_id' => $final->id,
        ]);
        $this->assertTrue(AuditLog::query()->where('action', 'champion.crowned')->exists());
        $this->actingAs($admin)->get(route('champions.index'))
            ->assertOk()
            ->assertSee($players[0]->nickname)
            ->assertSee($tournament->name);
    }

    public function test_double_elimination_grand_final_crowns_undefeated_winner(): void
    {
        $admin = $this->administrator();
        [$tournament, $grandFinal, $players, $reset] = $this->doubleFinal($admin->id);

        $this->actingAs($admin)->post(route('matches.results.store', $grandFinal), [
            'games' => [['participant_a_score' => 2, 'participant_b_score' => 0]],
        ])->assertRedirect();

        $this->assertSame(MatchStatus::Cancelled, $reset->refresh()->status);
        $this->assertSame($players[0]->id, TournamentChampion::query()->where('tournament_id', $tournament->id)->value('participant_id'));
    }

    public function test_loser_bracket_win_waits_for_reset_before_crowning(): void
    {
        $admin = $this->administrator();
        [$tournament, $grandFinal, $players, $reset] = $this->doubleFinal($admin->id);

        $this->actingAs($admin)->post(route('matches.results.store', $grandFinal), [
            'games' => [['participant_a_score' => 0, 'participant_b_score' => 2]],
        ])->assertRedirect();
        $this->assertDatabaseMissing('tournament_champions', ['tournament_id' => $tournament->id]);

        $this->actingAs($admin)->post(route('matches.results.store', $reset->refresh()), [
            'games' => [['participant_a_score' => 1, 'participant_b_score' => 3]],
        ])->assertRedirect();

        $this->assertSame($players[1]->id, TournamentChampion::query()->where('tournament_id', $tournament->id)->value('participant_id'));
        $this->assertSame($reset->id, TournamentChampion::query()->where('tournament_id', $tournament->id)->value('deciding_match_id'));
    }

    public function test_correcting_grand_final_can_revoke_champion_until_reset(): void
    {
        $admin = $this->administrator();
        [$tournament, $grandFinal, $players, $reset] = $this->doubleFinal($admin->id);
        $this->actingAs($admin)->post(route('matches.results.store', $grandFinal), [
            'games' => [['participant_a_score' => 2, 'participant_b_score' => 0]],
        ]);
        $this->assertDatabaseHas('tournament_champions', ['tournament_id' => $tournament->id]);

        $this->actingAs($admin)->put(route('matches.results.update', $grandFinal), [
            'games' => [['participant_a_score' => 0, 'participant_b_score' => 1]],
        ])->assertRedirect();

        $this->assertDatabaseMissing('tournament_champions', ['tournament_id' => $tournament->id]);
        $this->assertSame(MatchStatus::Pending, $reset->refresh()->status);
        $this->assertTrue(AuditLog::query()->where('action', 'champion.revoked')->exists());
    }

    /** @return array{Tournament, GameMatch, Collection<int, Player>} */
    private function finalMatch(TournamentFormat $format, int $userId): array
    {
        $tournament = Tournament::factory()->create([
            'format' => $format,
            'status' => TournamentStatus::InProgress,
            'best_of' => BestOf::One,
        ]);
        $players = Player::factory()->count(2)->create();
        $tournament->players()->attach($players->pluck('id')->all(), [
            'registered_by' => $userId,
            'source' => 'manual',
            'registered_at' => now(),
        ]);
        $round = Round::factory()->create([
            'tournament_id' => $tournament->id,
            'name' => 'Final',
            'number' => 1,
            'bracket' => BracketType::Main,
        ]);
        $match = GameMatch::factory()->create([
            'tournament_id' => $tournament->id,
            'round_id' => $round->id,
            'participant_a_id' => $players[0]->id,
            'participant_b_id' => $players[1]->id,
            'best_of' => BestOf::One,
        ]);

        return [$tournament, $match, $players];
    }

    /** @return array{Tournament, GameMatch, Collection<int, Player>, GameMatch} */
    private function doubleFinal(int $userId): array
    {
        [$tournament, $grandFinal, $players] = $this->finalMatch(TournamentFormat::DoubleElimination, $userId);
        $grandFinal->round->update(['bracket' => BracketType::Finals]);
        $resetRound = Round::factory()->create([
            'tournament_id' => $tournament->id,
            'name' => 'Final de reinicio',
            'number' => 2,
            'bracket' => BracketType::Finals,
        ]);
        $reset = GameMatch::factory()->create([
            'tournament_id' => $tournament->id,
            'round_id' => $resetRound->id,
            'participant_a_id' => null,
            'participant_b_id' => null,
            'is_conditional' => true,
            'best_of' => BestOf::One,
        ]);

        return [$tournament, $grandFinal, $players, $reset];
    }
}
