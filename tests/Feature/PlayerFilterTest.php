<?php

namespace Tests\Feature;

use App\Enums\PlayerLevel;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_players_can_be_filtered_by_search_country_level_and_status(): void
    {
        $user = $this->administrator();
        $expected = Player::factory()->create([
            'name' => 'Ana Campeona',
            'nickname' => 'QueenFC',
            'country' => 'Costa Rica',
            'level' => PlayerLevel::Professional,
            'is_active' => true,
        ]);
        Player::factory()->inactive()->create([
            'nickname' => 'OtherPlayer',
            'country' => 'Panamá',
            'level' => PlayerLevel::Beginner,
        ]);

        $this->actingAs($user)->get(route('players.index', [
            'search' => 'Queen',
            'country' => 'Costa Rica',
            'level' => PlayerLevel::Professional->value,
            'is_active' => '1',
        ]))->assertOk()->assertSee($expected->nickname)->assertDontSee('OtherPlayer');
    }
}
