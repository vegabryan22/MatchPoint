<?php

namespace Tests\Feature;

use App\Enums\BestOf;
use App\Enums\GameType;
use App\Enums\ParticipantType;
use App\Enums\TournamentFormat;
use App\Enums\TournamentStatus;
use App\Models\AuditLog;
use App\Models\Player;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_update_duplicate_and_delete_draft(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->post(route('tournaments.store'), $this->validData())
            ->assertRedirect();

        $tournament = Tournament::query()->where('name', 'Copa MatchPoint')->firstOrFail();
        $this->assertSame(TournamentStatus::Draft, $tournament->status);
        $this->assertSame('copa-matchpoint', $tournament->slug);
        $this->assertSame($admin->id, $tournament->created_by);

        $this->actingAs($admin)->put(route('tournaments.update', $tournament), [
            ...$this->validData(),
            'name' => 'Copa MatchPoint Pro',
            'best_of' => BestOf::Three->value,
        ])->assertRedirect(route('tournaments.show', $tournament));

        $this->assertDatabaseHas('tournaments', [
            'id' => $tournament->id,
            'name' => 'Copa MatchPoint Pro',
            'slug' => 'copa-matchpoint',
            'best_of' => 3,
        ]);

        $this->actingAs($admin)->post(route('tournaments.duplicate', $tournament))->assertRedirect();
        $copy = Tournament::query()->where('id', '!=', $tournament->id)->firstOrFail();
        $this->assertSame(TournamentStatus::Draft, $copy->status);
        $this->assertNotSame($tournament->slug, $copy->slug);
        $this->assertNull($copy->registration_starts_at);

        $this->actingAs($admin)->delete(route('tournaments.destroy', $copy))->assertRedirect(route('tournaments.index'));
        $this->assertSoftDeleted('tournaments', ['id' => $copy->id]);
    }

    public function test_tournament_pages_show_registered_participant_occupancy(): void
    {
        $admin = $this->administrator();
        $tournament = Tournament::factory()->create([
            'created_by' => $admin,
            'participant_type' => ParticipantType::Individual,
            'max_participants' => 16,
        ]);
        $players = Player::factory()->count(3)->create();
        $tournament->players()->attach($players->pluck('id'), [
            'source' => 'manual',
            'registered_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('tournaments.show', $tournament))
            ->assertOk()
            ->assertSee('Inscripciones (3/16)')
            ->assertSee('3 de 16')
            ->assertSee(route('tournaments.registrations.index', $tournament));

        $this->actingAs($admin)->get(route('tournaments.index'))
            ->assertOk()
            ->assertSee('3 / 16');
    }

    public function test_tournament_follows_only_allowed_status_transitions(): void
    {
        $admin = $this->administrator();
        $tournament = Tournament::factory()->create([
            'created_by' => $admin,
            'status' => TournamentStatus::Draft,
            'ends_at' => null,
            'extraordinary_registration_enabled' => true,
        ]);

        foreach ([TournamentStatus::Registration, TournamentStatus::InProgress, TournamentStatus::Finished] as $status) {
            $this->actingAs($admin)->patch(route('tournaments.status', $tournament), [
                'status' => $status->value,
            ])->assertRedirect();
            $this->assertSame($status, $tournament->refresh()->status);
        }

        $this->assertNotNull($tournament->ends_at);
        $this->assertFalse($tournament->extraordinary_registration_enabled);

        $this->actingAs($admin)->patch(route('tournaments.status', $tournament), [
            'status' => TournamentStatus::Draft->value,
        ])->assertForbidden();
        $this->assertSame(TournamentStatus::Finished, $tournament->refresh()->status);
    }

    public function test_active_tournament_cannot_be_reconfigured_or_deleted(): void
    {
        $admin = $this->administrator();
        $tournament = Tournament::factory()->status(TournamentStatus::InProgress)->create(['created_by' => $admin]);

        $this->actingAs($admin)->put(route('tournaments.update', $tournament), $this->validData())
            ->assertSessionHasErrors('tournament');
        $this->actingAs($admin)->delete(route('tournaments.destroy', $tournament))
            ->assertSessionHasErrors('tournament');

        $this->assertDatabaseHas('tournaments', ['id' => $tournament->id, 'deleted_at' => null]);
    }

    public function test_finished_tournament_is_read_only_and_does_not_advertise_public_registration(): void
    {
        $admin = $this->administrator();
        $tournament = Tournament::factory()->status(TournamentStatus::Finished)->create([
            'created_by' => $admin,
            'quick_registration_enabled' => true,
            'extraordinary_registration_enabled' => true,
        ]);

        $this->actingAs($admin)->get(route('tournaments.show', $tournament))
            ->assertOk()
            ->assertDontSee('QR del formulario público')
            ->assertDontSee(route('tournaments.edit', $tournament));

        $this->actingAs($admin)->get(route('tournaments.edit', $tournament))->assertForbidden();
        $this->actingAs($admin)->put(route('tournaments.update', $tournament), $this->validData())->assertForbidden();
    }

    public function test_custom_game_is_required_and_standard_game_discards_custom_value(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->post(route('tournaments.store'), [
            ...$this->validData(),
            'game' => GameType::Other->value,
            'custom_game' => null,
        ])->assertSessionHasErrors('custom_game');

        $this->actingAs($admin)->post(route('tournaments.store'), [
            ...$this->validData(),
            'custom_game' => 'No debe persistir',
        ])->assertRedirect();

        $this->assertDatabaseHas('tournaments', ['name' => 'Copa MatchPoint', 'custom_game' => null]);
    }

    public function test_quick_registration_levels_are_stored_from_checkbox_selection(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->get(route('tournaments.create'))
            ->assertOk()
            ->assertSee('Sétimo 7')
            ->assertSee('Octavo 8')
            ->assertSee('Noveno 9')
            ->assertSee('Décimo 10')
            ->assertSee('Undécimo 11')
            ->assertSee('Duodécimo 12');

        $this->actingAs($admin)->post(route('tournaments.store'), [
            ...$this->validData(),
            'quick_registration_enabled' => '1',
            'quick_registration_levels' => ['7', '8', '9'],
            'quick_registration_notice' => 'Traer control propio.',
        ])->assertRedirect();

        $tournament = Tournament::query()->where('name', 'Copa MatchPoint')->firstOrFail();

        $this->assertTrue($tournament->quick_registration_enabled);
        $this->assertSame(['7', '8', '9'], $tournament->quick_registration_levels);
    }

    public function test_world_cup_format_requires_capacity_48(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->post(route('tournaments.store'), [
            ...$this->validData(),
            'format' => TournamentFormat::WorldCup48->value,
            'max_participants' => 32,
        ])->assertSessionHasErrors('max_participants');

        $this->actingAs($admin)->post(route('tournaments.store'), [
            ...$this->validData(),
            'format' => TournamentFormat::WorldCup48->value,
            'max_participants' => 48,
        ])->assertRedirect();

        $this->assertDatabaseHas('tournaments', [
            'format' => TournamentFormat::WorldCup48->value,
            'max_participants' => 48,
        ]);
    }

    public function test_duplication_and_status_changes_are_audited(): void
    {
        $admin = $this->administrator();
        $tournament = Tournament::factory()->create(['created_by' => $admin]);

        $this->actingAs($admin)->post(route('tournaments.duplicate', $tournament));
        $this->actingAs($admin)->patch(route('tournaments.status', $tournament), [
            'status' => TournamentStatus::Registration->value,
        ]);

        $this->assertTrue(AuditLog::query()->where('action', 'tournament.duplicated')->where('auditable_id', $tournament->id)->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'tournament.status_changed')->where('auditable_id', $tournament->id)->exists());
    }

    private function validData(): array
    {
        return [
            'name' => 'Copa MatchPoint',
            'description' => 'Competencia oficial de EA Sports FC.',
            'game' => GameType::EaSportsFc->value,
            'custom_game' => null,
            'participant_type' => ParticipantType::Individual->value,
            'max_participants' => 16,
            'format' => TournamentFormat::SingleElimination->value,
            'best_of' => BestOf::One->value,
            'registration_starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'registration_ends_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'starts_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'ends_at' => null,
        ];
    }
}
