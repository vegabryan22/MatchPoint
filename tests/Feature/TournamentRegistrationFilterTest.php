<?php

namespace Tests\Feature;

use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentRegistrationFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_participants_can_be_searched(): void
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament();
        $expected = Player::factory()->create(['nickname' => 'SearchChampion']);
        $other = Player::factory()->create(['nickname' => 'HiddenRival']);
        $tournament->players()->attach([$expected->id, $other->id], [
            'registered_by' => $admin->id,
            'source' => 'manual',
            'registered_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('tournaments.registrations.index', [
            'tournament' => $tournament,
            'search' => 'SearchChampion',
        ]))->assertOk()->assertSee('SearchChampion')->assertDontSee('HiddenRival');
    }
}
