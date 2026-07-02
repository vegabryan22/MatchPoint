<?php

namespace Tests\Feature;

use App\Enums\MatchStatus;
use App\Enums\ParticipantType;
use App\Enums\RoleName;
use App\Enums\TournamentFormat;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Role;
use App\Models\Round;
use App\Models\Tournament;
use App\Models\TournamentStation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_distributes_matches_between_active_stations_and_respects_rounds(): void
    {
        $admin = $this->administrator();
        $tournament = Tournament::factory()->create([
            'created_by' => $admin,
            'participant_type' => ParticipantType::Individual,
            'starts_at' => '2026-07-01 08:00:00',
            'match_duration_minutes' => 15,
            'turnaround_minutes' => 5,
        ]);
        TournamentStation::factory()->count(2)->sequence(
            ['name' => 'Consola 1'],
            ['name' => 'Consola 2'],
        )->create(['tournament_id' => $tournament]);
        $firstRound = Round::factory()->create(['tournament_id' => $tournament, 'number' => 1]);
        $secondRound = Round::factory()->create(['tournament_id' => $tournament, 'number' => 2]);
        $this->createMatches($tournament, $firstRound, 4);
        $this->createMatches($tournament, $secondRound, 2);

        $this->actingAs($admin)->post(route('tournaments.schedule.generate', $tournament), [
            'starts_at' => '2026-07-01 08:00:00',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $firstRoundMatches = GameMatch::query()->where('round_id', $firstRound->id)->orderBy('sequence')->get();
        $secondRoundMatches = GameMatch::query()->where('round_id', $secondRound->id)->orderBy('sequence')->get();

        $this->assertSame(['08:00', '08:00', '08:20', '08:20'], $firstRoundMatches->map(fn (GameMatch $match) => $match->scheduled_at->format('H:i'))->all());
        $this->assertSame(['08:40', '08:40'], $secondRoundMatches->map(fn (GameMatch $match) => $match->scheduled_at->format('H:i'))->all());
        $this->assertSame('08:55', $secondRoundMatches->max('scheduled_end_at')->format('H:i'));
        $this->assertSame(2, $firstRoundMatches->pluck('tournament_station_id')->unique()->count());

        $this->actingAs($admin)->get(route('tournaments.schedule.index', $tournament))
            ->assertOk()
            ->assertSee('2 activas')
            ->assertSee('Consola 1')
            ->assertSee('01/07/2026 08:55');
    }

    public function test_schedule_requires_an_active_station_and_generated_matches(): void
    {
        $admin = $this->administrator();
        $tournament = Tournament::factory()->create(['created_by' => $admin]);

        $this->actingAs($admin)->post(route('tournaments.schedule.generate', $tournament))
            ->assertSessionHasErrors('schedule');

        TournamentStation::factory()->create(['tournament_id' => $tournament, 'is_active' => true]);
        $this->actingAs($admin)->post(route('tournaments.schedule.generate', $tournament))
            ->assertSessionHasErrors('schedule');
    }

    public function test_capacity_calculator_finds_four_consoles_for_thirty_two_player_knockout_in_three_hours(): void
    {
        $admin = $this->administrator();
        $tournament = Tournament::factory()->create([
            'created_by' => $admin,
            'format' => TournamentFormat::SingleElimination,
            'participant_type' => ParticipantType::Individual,
            'match_duration_minutes' => 15,
            'turnaround_minutes' => 5,
        ]);
        $players = Player::factory()->count(32)->create();
        $tournament->players()->attach($players->pluck('id'), ['source' => 'manual', 'registered_at' => now()]);
        TournamentStation::factory()->count(4)->create(['tournament_id' => $tournament]);

        $this->actingAs($admin)->get(route('tournaments.schedule.index', [
            'tournament' => $tournament,
            'target_hours' => 3,
            'target_minutes' => 0,
        ]))
            ->assertOk()
            ->assertSee('31')
            ->assertSee('2 h 55 min')
            ->assertSee('Mínimo:')
            ->assertSee('4 consolas');
    }

    public function test_world_cup_projection_uses_one_hundred_three_matches(): void
    {
        $admin = $this->administrator();
        $tournament = Tournament::factory()->create([
            'created_by' => $admin,
            'format' => TournamentFormat::WorldCup48,
            'participant_type' => ParticipantType::Individual,
            'max_participants' => 48,
            'match_duration_minutes' => 15,
            'turnaround_minutes' => 5,
        ]);
        $players = Player::factory()->count(48)->create();
        $tournament->players()->attach($players->pluck('id'), ['source' => 'manual', 'registered_at' => now()]);
        TournamentStation::factory()->count(8)->create(['tournament_id' => $tournament]);

        $this->actingAs($admin)->get(route('tournaments.schedule.index', [
            'tournament' => $tournament,
            'target_hours' => 5,
            'target_minutes' => 0,
        ]))
            ->assertOk()
            ->assertSee('103')
            ->assertSee('4 h 55 min')
            ->assertSee('8 consolas');
    }

    public function test_only_assigned_organizer_manages_schedule_while_referee_can_view_it(): void
    {
        $admin = $this->administrator();
        $organizer = $this->userWithRole(RoleName::Organizer);
        $referee = $this->userWithRole(RoleName::Referee);
        $outsider = $this->userWithRole(RoleName::Organizer);
        $tournament = Tournament::factory()->create(['created_by' => $admin]);
        $tournament->organizers()->attach($organizer, ['assigned_by' => $admin->id, 'is_primary' => true, 'assigned_at' => now()]);
        $tournament->officials()->attach($referee, ['assigned_by' => $admin->id, 'role' => 'referee', 'is_active' => true, 'assigned_at' => now()]);

        $this->actingAs($organizer)->post(route('tournaments.stations.store', $tournament), [
            'name' => 'PS5 Principal',
            'platform' => 'ps5',
            'location' => 'Gimnasio',
            'is_active' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->actingAs($referee)->get(route('tournaments.schedule.index', $tournament))->assertOk();
        $this->actingAs($referee)->put(route('tournaments.schedule.configure', $tournament), [
            'match_duration_minutes' => 20,
            'turnaround_minutes' => 5,
        ])->assertForbidden();
        $this->actingAs($outsider)->get(route('tournaments.schedule.index', $tournament))->assertForbidden();
    }

    public function test_station_from_another_tournament_cannot_be_updated_or_deleted(): void
    {
        $admin = $this->administrator();
        $tournament = Tournament::factory()->create(['created_by' => $admin]);
        $foreignTournament = Tournament::factory()->create(['created_by' => $admin]);
        $station = TournamentStation::factory()->create(['tournament_id' => $foreignTournament]);
        $payload = ['name' => 'Alterada', 'platform' => 'ps5', 'location' => null, 'is_active' => 1];

        $this->actingAs($admin)->put(route('tournaments.stations.update', [$tournament, $station]), $payload)->assertNotFound();
        $this->actingAs($admin)->delete(route('tournaments.stations.destroy', [$tournament, $station]))->assertNotFound();
    }

    private function createMatches(Tournament $tournament, Round $round, int $count): void
    {
        foreach (range(1, $count) as $sequence) {
            GameMatch::factory()->create([
                'tournament_id' => $tournament,
                'round_id' => $round,
                'sequence' => $sequence,
                'status' => MatchStatus::Pending,
                'is_conditional' => false,
            ]);
        }
    }

    private function userWithRole(RoleName $roleName): User
    {
        $role = Role::query()->firstOrCreate(['slug' => $roleName->value], ['name' => $roleName->label()]);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
