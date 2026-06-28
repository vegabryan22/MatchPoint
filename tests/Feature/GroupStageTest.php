<?php

namespace Tests\Feature;

use App\Enums\BracketType;
use App\Enums\MatchStatus;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Models\Player;
use App\Models\TournamentChampion;
use App\Models\User;
use App\Services\GroupStageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupStageTest extends TestCase
{
    use RefreshDatabase;

    public function test_round_robin_generation_creates_one_group_and_complete_schedule(): void
    {
        [$admin, $tournament, $players] = $this->competition(TournamentFormat::RoundRobin, 4);

        $this->actingAs($admin)->post(route('tournaments.groups.store', $tournament), [
            'group_count' => 1,
            'qualifiers_per_group' => 0,
        ])->assertRedirect(route('tournaments.groups.show', $tournament));

        $this->assertSame(1, $tournament->groups()->count());
        $this->assertSame(3, $tournament->rounds()->where('bracket', BracketType::Group)->count());
        $this->assertSame(6, $tournament->matches()->whereNotNull('group_id')->count());
        $pairs = $tournament->matches()->get()->map(fn ($match): string => min($match->participant_a_id, $match->participant_b_id).'-'.max($match->participant_a_id, $match->participant_b_id));
        $this->assertCount(6, $pairs->unique());

        $newPlayer = Player::factory()->create();
        $this->actingAs($admin)->post(route('tournaments.registrations.store', $tournament), ['participant_id' => $newPlayer->id])
            ->assertSessionHasErrors('registration');
    }

    public function test_group_stage_balances_participants_and_creates_each_group_schedule(): void
    {
        [$admin, $tournament] = $this->competition(TournamentFormat::GroupsKnockout, 8);
        $this->actingAs($admin)->post(route('tournaments.groups.store', $tournament), [
            'group_count' => 2,
            'qualifiers_per_group' => 2,
        ])->assertRedirect();

        $groups = $tournament->groups()->withCount('participants')->get();
        $this->assertSame([4, 4], $groups->pluck('participants_count')->all());
        $this->assertSame(12, $tournament->matches()->whereNotNull('group_id')->count());
        $this->assertSame(3, $tournament->rounds()->where('bracket', BracketType::Group)->count());
    }

    public function test_group_result_accepts_draw_and_updates_points_table(): void
    {
        [$admin, $tournament] = $this->competition(TournamentFormat::RoundRobin, 3);
        $this->generate($admin, $tournament, 1, 0);
        $tournament->update(['status' => TournamentStatus::InProgress]);
        $match = $tournament->matches()->firstOrFail();

        $this->actingAs($admin)->post(route('matches.results.store', $match), [
            'games' => [['participant_a_score' => 2, 'participant_b_score' => 2]],
        ])->assertRedirect();

        $this->assertNull($match->refresh()->winner_id);
        $this->assertDatabaseHas('scores', ['match_id' => $match->id, 'winner_id' => null]);
        $this->actingAs($admin)->get(route('tournaments.groups.show', $tournament))->assertOk();
        $table = app(GroupStageService::class)->details($tournament)['standings']->first();
        $this->assertSame([1, 1], $table->pluck('points')->filter()->sort()->values()->all());
        $this->assertSame([2, 2], $table->pluck('goals_for')->filter()->sort()->values()->all());
    }

    public function test_completed_groups_generate_cross_group_knockout_bracket(): void
    {
        [$admin, $tournament] = $this->competition(TournamentFormat::GroupsKnockout, 8);
        $this->generate($admin, $tournament, 2, 2);
        $tournament->update(['status' => TournamentStatus::InProgress]);

        foreach ($tournament->matches()->whereNotNull('group_id')->get() as $match) {
            $match->update(['winner_id' => $match->participant_a_id, 'status' => MatchStatus::Completed, 'completed_at' => now()]);
            $match->scores()->create(['game_number' => 1, 'participant_a_score' => 1, 'participant_b_score' => 0, 'winner_id' => $match->participant_a_id]);
        }

        $this->actingAs($admin)->post(route('tournaments.groups.qualify', $tournament))->assertRedirect();

        $this->assertSame(2, $tournament->rounds()->where('bracket', BracketType::Main)->count());
        $this->assertSame(3, $tournament->matches()->whereNull('group_id')->count());
        $participantGroups = $tournament->groups()->with('participants')->get()->reduce(function ($map, $group) {
            foreach ($group->participants as $entry) {
                $map[$entry->participant_id] = $group->id;
            }

            return $map;
        }, collect());
        foreach ($tournament->rounds()->where('bracket', BracketType::Main)->where('number', 1)->firstOrFail()->matches as $match) {
            $this->assertNotSame($participantGroups[$match->participant_a_id], $participantGroups[$match->participant_b_id]);
        }
    }

    public function test_league_crowns_table_leader_after_last_result(): void
    {
        [$admin, $tournament] = $this->competition(TournamentFormat::League, 3);
        $this->generate($admin, $tournament, 1, 0);
        $tournament->update(['status' => TournamentStatus::InProgress]);
        $matches = $tournament->matches()->orderBy('id')->get();

        foreach ($matches as $match) {
            $this->actingAs($admin)->post(route('matches.results.store', $match), [
                'games' => [['participant_a_score' => 2, 'participant_b_score' => 0]],
            ])->assertRedirect();
        }

        $this->assertDatabaseHas('tournament_champions', ['tournament_id' => $tournament->id]);
        $this->assertNotNull(TournamentChampion::query()->where('tournament_id', $tournament->id)->first()->participant_id);
    }

    public function test_regular_user_can_view_but_cannot_generate_groups(): void
    {
        [$admin, $tournament] = $this->competition(TournamentFormat::RoundRobin, 4);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('tournaments.groups.show', $tournament))->assertOk();
        $this->actingAs($user)->post(route('tournaments.groups.store', $tournament), [
            'group_count' => 1, 'qualifiers_per_group' => 0,
        ])->assertForbidden();
    }

    private function generate($admin, $tournament, int $groups, int $qualifiers): void
    {
        $this->actingAs($admin)->post(route('tournaments.groups.store', $tournament), [
            'group_count' => $groups,
            'qualifiers_per_group' => $qualifiers,
        ])->assertRedirect();
    }

    private function competition(TournamentFormat $format, int $count): array
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament(attributes: [
            'format' => $format,
            'max_participants' => max(4, $count),
        ]);
        $players = Player::factory()->count($count)->create();
        $tournament->players()->attach($players->pluck('id')->all(), [
            'registered_by' => $admin->id,
            'source' => 'manual',
            'registered_at' => now(),
        ]);

        return [$admin, $tournament, $players];
    }
}
