<?php

namespace Tests\Feature;

use App\Enums\GameType;
use App\Enums\ParticipantType;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_tournaments_can_be_filtered_by_configuration(): void
    {
        $user = $this->administrator();
        Tournament::factory()->create([
            'name' => 'Copa Tica Pro',
            'status' => TournamentStatus::Registration,
            'game' => GameType::EaSportsFc,
            'format' => TournamentFormat::SingleElimination,
            'participant_type' => ParticipantType::Individual,
        ]);
        Tournament::factory()->create([
            'name' => 'Liga Rival',
            'status' => TournamentStatus::Draft,
            'game' => GameType::Pes,
            'format' => TournamentFormat::League,
            'participant_type' => ParticipantType::Team,
        ]);

        $this->actingAs($user)->get(route('tournaments.index', [
            'search' => 'Tica',
            'status' => TournamentStatus::Registration->value,
            'game' => GameType::EaSportsFc->value,
            'format' => TournamentFormat::SingleElimination->value,
            'participant_type' => ParticipantType::Individual->value,
        ]))->assertOk()->assertSee('Copa Tica Pro')->assertDontSee('Liga Rival');
    }
}
