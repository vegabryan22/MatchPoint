<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Tournament;
use Database\Seeders\LargeBracketDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LargeBracketDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_generates_complete_repeatable_brackets_for_32_and_64_players(): void
    {
        $this->seed(LargeBracketDemoSeeder::class);

        $bracket32 = Tournament::query()->where('slug', 'mundial-matchpoint-32')->firstOrFail();
        $bracket48 = Tournament::query()->where('slug', 'mundial-matchpoint-48-eliminacion')->firstOrFail();
        $bracket64 = Tournament::query()->where('slug', 'mundial-matchpoint-64')->firstOrFail();
        $worldCup48 = Tournament::query()->where('slug', 'mundial-matchpoint-48-oficial')->firstOrFail();

        $this->assertSame(64, Player::query()->where('email', 'like', 'world-seed-%')->count());
        $this->assertSame(32, $bracket32->players()->count());
        $this->assertSame(5, $bracket32->rounds()->count());
        $this->assertSame(31, $bracket32->matches()->count());
        $this->assertSame(48, $bracket48->players()->count());
        $this->assertSame(63, $bracket48->matches()->count());
        $this->assertSame(16, $bracket48->matches()->where('status', 'bye')->count());
        $this->assertSame(64, $bracket64->players()->count());
        $this->assertSame(6, $bracket64->rounds()->count());
        $this->assertSame(63, $bracket64->matches()->count());
        $this->assertSame(12, $worldCup48->groups()->count());
        $this->assertSame(72, $worldCup48->matches()->whereNotNull('group_id')->count());
        $this->assertSame(31, $worldCup48->matches()->whereNull('group_id')->count());

        $this->actingAs($bracket64->creator)->get(route('tournaments.draws.show', $bracket64))
            ->assertOk()
            ->assertSee('mp-world-bracket is-symmetric', false)
            ->assertSee('WorldSeed64');

        $this->seed(LargeBracketDemoSeeder::class);

        $this->assertSame(31, $bracket32->matches()->count());
        $this->assertSame(63, $bracket48->matches()->count());
        $this->assertSame(63, $bracket64->matches()->count());
        $this->assertSame(103, $worldCup48->matches()->count());
    }
}
