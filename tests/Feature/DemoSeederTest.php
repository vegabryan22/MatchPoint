<?php

namespace Tests\Feature;

use App\Enums\GameClubType;
use App\Enums\MatchStatus;
use App\Enums\TournamentStatus;
use App\Models\GameClub;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_builds_complete_repeatable_scenarios(): void
    {
        $this->seed(DemoSeeder::class);

        $completed = Tournament::query()->where('slug', 'copa-matchpoint-2026')->firstOrFail();
        $groups = Tournament::query()->where('slug', 'liga-esports-san-jose')->firstOrFail();
        $registration = Tournament::query()->where('slug', 'clasificatorio-fc-costa-rica')->firstOrFail();

        $this->assertSame(16, Player::query()->count());
        $this->assertSame(4, Team::query()->count());
        $this->assertSame(8, GameClub::query()->count());
        $this->assertSame(8, GameClub::query()->where('team_type', GameClubType::NationalTeam)->count());
        $this->assertDatabaseHas('game_clubs', ['name' => 'Costa Rica', 'country_code' => 'CR']);
        $this->assertSame(8, $completed->playerRegistrations()->whereNotNull('game_club_id')->count());
        $this->assertSame(3, Tournament::query()->count());
        $this->assertSame(TournamentStatus::Finished, $completed->status);
        $this->assertSame(7, $completed->matches()->where('status', MatchStatus::Completed)->count());
        $this->assertTrue($completed->champion()->exists());
        $this->assertSame(TournamentStatus::InProgress, $groups->status);
        $this->assertSame(2, $groups->groups()->count());
        $this->assertSame(6, $groups->matches()->where('status', MatchStatus::Completed)->count());
        $this->assertSame(6, $groups->matches()->where('status', MatchStatus::Pending)->count());
        $this->assertSame(TournamentStatus::Registration, $registration->status);
        $this->assertSame(6, $registration->players()->count());
        $this->assertTrue(User::query()->where('email', 'organizer@example.com')->exists());
        $this->assertTrue(User::query()->where('email', 'referee@example.com')->exists());

        $this->seed(DemoSeeder::class);

        $this->assertSame(16, Player::query()->count());
        $this->assertSame(3, Tournament::query()->count());
    }
}
