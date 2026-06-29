<?php

namespace Tests\Feature;

use App\Enums\DrawMethod;
use App\Enums\GameClubType;
use App\Enums\GameType;
use App\Enums\TournamentStatus;
use App\Models\GameClub;
use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GameClubTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_imports_popular_clubs_and_external_badges(): void
    {
        Http::fake(function ($request) {
            $name = $request->data()['t'];

            return Http::response(['teams' => [[
                'idTeam' => (string) abs(crc32($name)),
                'strTeam' => $name,
                'strSport' => 'Soccer',
                'strBadge' => 'https://images.example.com/'.str($name)->slug().'.png',
            ]]]);
        });

        $this->actingAs($this->administrator())->post(route('game-clubs.import-popular'), ['games' => [
            GameType::EaSportsFc->value,
            GameType::Fifa->value,
            GameType::Pes->value,
        ], 'catalogs' => ['clubs', 'national_teams']])->assertRedirect();

        $this->assertSame(48, GameClub::query()->count());
        $this->assertDatabaseHas('game_clubs', ['name' => 'Real Madrid', 'external_provider' => 'thesportsdb']);
        $this->assertDatabaseHas('game_clubs', ['name' => 'Costa Rica', 'team_type' => GameClubType::NationalTeam->value, 'country_code' => 'CR']);
        $realMadrid = GameClub::query()->where('name', 'Real Madrid')->firstOrFail();
        $this->assertSame(3, $realMadrid->availabilities()->count());
        $this->assertDatabaseHas('game_club_availabilities', ['game_club_id' => $realMadrid->id, 'game' => GameType::Fifa->value]);
        $this->assertDatabaseHas('game_club_availabilities', ['game_club_id' => $realMadrid->id, 'game' => GameType::Pes->value]);
        $this->assertSame('https://images.example.com/real-madrid.png', GameClub::query()->where('name', 'Real Madrid')->firstOrFail()->crestUrl());

        $this->actingAs($this->administrator())->get(route('game-clubs.index', ['team_type' => GameClubType::NationalTeam->value]))
            ->assertOk()
            ->assertSee('Costa Rica')
            ->assertSee('Selección nacional')
            ->assertSee('EA Sports FC')
            ->assertSee('FIFA')
            ->assertSee('PES');
    }

    public function test_administrator_manages_game_club_with_crest(): void
    {
        Storage::fake('public');
        $admin = $this->administrator();

        $this->actingAs($admin)->post(route('game-clubs.store'), [
            'name' => 'Club Aurora',
            'team_type' => GameClubType::Club->value,
            'games' => [GameType::EaSportsFc->value, GameType::Fifa->value],
            'crest' => $this->fakeImage('aurora.png'),
            'is_active' => '1',
        ])->assertRedirect(route('game-clubs.index'));

        $club = GameClub::query()->firstOrFail();
        Storage::disk('public')->assertExists($club->crest_path);
        $this->assertSame(2, $club->availabilities()->count());
        $this->actingAs($admin)->get(route('game-clubs.index'))->assertOk()->assertSee('Club Aurora');
    }

    public function test_deleting_local_crest_does_not_require_flysystem_mime_detection(): void
    {
        $path = storage_path('app/public/game-clubs/delete-test.svg');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, '<svg xmlns="http://www.w3.org/2000/svg"/>');
        $club = GameClub::factory()->create(['crest_path' => 'game-clubs/delete-test.svg']);

        $this->actingAs($this->administrator())->delete(route('game-clubs.destroy', $club))->assertRedirect();

        $this->assertDatabaseMissing('game_clubs', ['id' => $club->id]);
        $this->assertFalse(File::exists($path));
    }

    public function test_organization_assigns_compatible_club_and_bracket_displays_it(): void
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament(attributes: ['game' => GameType::EaSportsFc]);
        $players = Player::factory()->count(4)->create();
        $tournament->players()->attach($players->pluck('id')->all(), ['registered_by' => $admin->id, 'source' => 'manual', 'registered_at' => now()]);
        $club = GameClub::factory()->create(['name' => 'Deportivo Central', 'crest_path' => 'game-clubs/central.png']);
        $wrongGame = GameClub::factory()->create();
        $wrongGame->availabilities()->update(['game' => GameType::Fifa->value]);

        $this->actingAs($admin)->patch(route('tournaments.registrations.game-club', [$tournament, $players[0]->id]), ['game_club_id' => $club->id])->assertRedirect();
        $this->assertDatabaseHas('tournament_players', ['tournament_id' => $tournament->id, 'player_id' => $players[0]->id, 'game_club_id' => $club->id]);
        $this->actingAs($admin)->patch(route('tournaments.registrations.game-club', [$tournament, $players[1]->id]), ['game_club_id' => $wrongGame->id])->assertSessionHasErrors('game_club_id');

        $this->actingAs($admin)->post(route('tournaments.draws.store', $tournament), [
            'method' => DrawMethod::Manual->value,
            'avoid_rematches' => '0',
            'resolved_order' => $players->pluck('id')->all(),
        ]);

        $this->actingAs($admin)->get(route('tournaments.draws.show', $tournament))
            ->assertOk()->assertSee('Deportivo Central')->assertSee('game-clubs/central.png');

        $tournament->update(['status' => TournamentStatus::InProgress]);
        $match = $tournament->matches()->where('participant_a_id', $players[0]->id)->orWhere('participant_b_id', $players[0]->id)->firstOrFail();
        $this->actingAs($admin)->get(route('matches.results.edit', $match))
            ->assertOk()->assertSee('Deportivo Central')->assertSee('game-clubs/central.png');
    }

    public function test_regular_user_cannot_manage_catalog_or_assign_club(): void
    {
        $user = User::factory()->create();
        $tournament = $this->registrationTournament();
        $player = Player::factory()->create();
        $tournament->players()->attach($player, ['source' => 'manual', 'registered_at' => now()]);
        $club = GameClub::factory()->create();

        $this->actingAs($user)->get(route('game-clubs.create'))->assertForbidden();
        $this->actingAs($user)->patch(route('tournaments.registrations.game-club', [$tournament, $player->id]), ['game_club_id' => $club->id])->assertForbidden();
    }
}
