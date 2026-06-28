<?php

namespace Tests\Feature;

use App\Enums\DrawMethod;
use App\Enums\MatchStatus;
use App\Enums\ParticipantType;
use App\Enums\PlayerLevel;
use App\Enums\TournamentFormat;
use App\Models\AuditLog;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Round;
use App\Services\TournamentDrawService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentDrawTest extends TestCase
{
    use RefreshDatabase;

    public function test_random_draw_assigns_seeds_creates_byes_and_locks_registrations(): void
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament(attributes: ['max_participants' => 8]);
        $players = Player::factory()->count(6)->create();
        $this->attachPlayers($tournament, $players, $admin->id);
        $order = $players->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $this->actingAs($admin)->post(route('tournaments.draws.store', $tournament), [
            'method' => DrawMethod::Random->value,
            'avoid_rematches' => '0',
            'resolved_order' => $order,
        ])->assertRedirect(route('tournaments.draws.show', $tournament));

        $this->assertDatabaseHas('tournament_draws', ['tournament_id' => $tournament->id, 'method' => 'random']);
        $this->assertSame(3, $tournament->rounds()->count());
        $this->assertSame(7, $tournament->matches()->count());
        $this->assertSame(2, $tournament->matches()->where('status', MatchStatus::Bye)->count());
        $this->assertSame(range(1, 6), $tournament->players()->pluck('tournament_players.seed')->sort()->values()->all());
        $this->assertTrue(AuditLog::query()->where('action', 'draw.generated')->where('auditable_id', $tournament->id)->exists());

        $newPlayer = Player::factory()->create();
        $this->actingAs($admin)->post(route('tournaments.registrations.store', $tournament), ['participant_id' => $newPlayer->id])
            ->assertSessionHasErrors('registration');
    }

    public function test_manual_seeding_respects_exact_order_and_rejects_invalid_set(): void
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament();
        $players = Player::factory()->count(4)->create();
        $this->attachPlayers($tournament, $players, $admin->id);
        $manualOrder = $players->pluck('id')->reverse()->values()->map(fn ($id): int => (int) $id)->all();
        $seeds = collect($manualOrder)->mapWithKeys(fn ($id, $index): array => [$id => $index + 1])->all();
        $service = app(TournamentDrawService::class);

        $plan = $service->preview($tournament, [
            'method' => DrawMethod::Manual->value,
            'avoid_rematches' => false,
            'seeds' => $seeds,
        ]);

        $this->assertSame($manualOrder, $plan['order']);

        $this->actingAs($admin)->post(route('tournaments.draws.preview', $tournament), [
            'method' => DrawMethod::Manual->value,
            'avoid_rematches' => '0',
            'seeds' => [$players[0]->id => 1],
        ])->assertSessionHasErrors('seeds');
        $this->assertDatabaseMissing('tournament_draws', ['tournament_id' => $tournament->id]);
    }

    public function test_automatic_seeding_prioritizes_competitive_level(): void
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament();
        $beginner = Player::factory()->create(['level' => PlayerLevel::Beginner, 'nickname' => 'Beginner']);
        $professional = Player::factory()->create(['level' => PlayerLevel::Professional, 'nickname' => 'Professional']);
        $advanced = Player::factory()->create(['level' => PlayerLevel::Advanced, 'nickname' => 'Advanced']);
        $this->attachPlayers($tournament, collect([$beginner, $professional, $advanced]), $admin->id);

        $plan = app(TournamentDrawService::class)->preview($tournament, [
            'method' => DrawMethod::Automatic->value,
            'avoid_rematches' => false,
        ]);

        $this->assertSame([$professional->id, $advanced->id, $beginner->id], $plan['order']);
    }

    public function test_rematch_avoidance_selects_an_alternative_opponent(): void
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament();
        $players = Player::factory()->count(4)->create();
        $this->attachPlayers($tournament, $players, $admin->id);
        $previousTournament = $this->registrationTournament();
        $previousRound = Round::factory()->create(['tournament_id' => $previousTournament->id]);
        GameMatch::factory()->create([
            'tournament_id' => $previousTournament->id,
            'round_id' => $previousRound->id,
            'participant_type' => ParticipantType::Individual,
            'participant_a_id' => $players[0]->id,
            'participant_b_id' => $players[3]->id,
            'winner_id' => $players[0]->id,
            'status' => MatchStatus::Completed,
        ]);

        $plan = app(TournamentDrawService::class)->preview($tournament, [
            'method' => DrawMethod::Manual->value,
            'avoid_rematches' => true,
            'seeds' => [
                $players[0]->id => 1,
                $players[1]->id => 2,
                $players[2]->id => 3,
                $players[3]->id => 4,
            ],
        ]);

        $this->assertSame($players[0]->id, $plan['pairs'][0]['participant_a_id']);
        $this->assertSame($players[2]->id, $plan['pairs'][0]['participant_b_id']);
    }

    public function test_draw_can_be_reset_before_results_but_not_after_completed_match(): void
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament();
        $players = Player::factory()->count(4)->create();
        $this->attachPlayers($tournament, $players, $admin->id);
        $data = [
            'method' => DrawMethod::Random->value,
            'avoid_rematches' => '0',
            'resolved_order' => $players->pluck('id')->all(),
        ];

        $this->actingAs($admin)->post(route('tournaments.draws.store', $tournament), $data);
        $this->actingAs($admin)->delete(route('tournaments.draws.destroy', $tournament))->assertRedirect();
        $this->assertDatabaseMissing('tournament_draws', ['tournament_id' => $tournament->id]);
        $this->assertTrue($tournament->players()->wherePivotNull('seed')->count() === 4);

        $this->actingAs($admin)->post(route('tournaments.draws.store', $tournament), $data);
        $tournament->matches()->firstOrFail()->update(['status' => MatchStatus::Completed]);
        $this->actingAs($admin)->delete(route('tournaments.draws.destroy', $tournament))->assertSessionHasErrors('draw');
        $this->assertDatabaseHas('tournament_draws', ['tournament_id' => $tournament->id]);
    }

    public function test_non_elimination_format_cannot_generate_bracket(): void
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament(attributes: ['format' => TournamentFormat::RoundRobin]);
        $players = Player::factory()->count(2)->create();
        $this->attachPlayers($tournament, $players, $admin->id);

        $this->actingAs($admin)->post(route('tournaments.draws.preview', $tournament), [
            'method' => DrawMethod::Random->value,
            'avoid_rematches' => '0',
        ])->assertSessionHasErrors('draw');
    }

    private function attachPlayers($tournament, $players, int $userId): void
    {
        $tournament->players()->attach($players->pluck('id')->all(), [
            'registered_by' => $userId,
            'source' => 'manual',
            'registered_at' => now(),
        ]);
    }
}
