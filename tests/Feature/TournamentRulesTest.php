<?php

namespace Tests\Feature;

use App\Enums\TournamentFormat;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_rules_document_is_printable_and_uses_current_registration_count(): void
    {
        $admin = $this->administrator();
        $tournament = $this->registrationTournament(attributes: [
            'format' => TournamentFormat::SingleElimination,
            'max_participants' => 48,
        ]);
        $players = Player::factory()->count(38)->create();
        $tournament->players()->attach($players->pluck('id'), ['source' => 'manual', 'registered_at' => now()]);

        $this->actingAs($admin)->get(route('tournaments.rules.print', $tournament))
            ->assertOk()
            ->assertSee('Reglamento oficial')
            ->assertSee('19')
            ->assertSee('13')
            ->assertSee('diferencia de tres goles')
            ->assertSee('Imprimir reglamento');
    }
}
