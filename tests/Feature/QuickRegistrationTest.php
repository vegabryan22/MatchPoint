<?php

namespace Tests\Feature;

use App\Enums\ParticipantType;
use App\Enums\TournamentStatus;
use App\Models\Player;
use App\Models\TournamentPlayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class QuickRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_registers_without_account_or_contact_data(): void
    {
        $tournament = $this->quickTournament();

        $response = $this->post(route('quick-registrations.store', $tournament), [
            'full_name' => 'Ana López',
            'username' => 'AnaGol',
            'academic_level' => '11',
            'controller_platform' => 'ps5',
            'bring_own_controller' => '1',
            'website' => null,
        ]);

        $registration = TournamentPlayer::query()->firstOrFail();
        $response->assertRedirect(route('quick-registrations.confirmation', [$tournament, $registration->public_reference]));
        $this->assertDatabaseHas('players', [
            'name' => 'Ana López',
            'nickname' => 'AnaGol',
            'email' => null,
            'country' => null,
            'is_quick_entry' => true,
        ]);
        $this->assertDatabaseHas('tournament_players', [
            'tournament_id' => $tournament->id,
            'source' => 'public',
            'academic_level' => '11',
            'controller_platform' => 'ps5',
            'registered_by' => null,
        ]);
        $this->assertNotNull($registration->controller_acknowledged_at);
        $this->assertNull(Player::query()->firstOrFail()->user_id);

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('AnaGol')
            ->assertSee($registration->public_reference);
    }

    public function test_public_form_requires_allowed_level_controller_acknowledgement_and_unique_username(): void
    {
        $tournament = $this->quickTournament();
        Player::factory()->create(['nickname' => 'Ocupado']);

        $this->post(route('quick-registrations.store', $tournament), [
            'full_name' => 'Estudiante Prueba',
            'username' => 'Ocupado',
            'academic_level' => '6',
            'controller_platform' => 'xbox',
            'website' => 'spam.example',
        ])->assertSessionHasErrors(['username', 'academic_level', 'controller_platform', 'bring_own_controller', 'website']);

        $this->assertSame(0, $tournament->players()->count());
    }

    public function test_public_registration_closes_when_disabled_full_or_wrong_modality(): void
    {
        $disabled = $this->quickTournament(['quick_registration_enabled' => false]);
        $this->get(route('quick-registrations.create', $disabled))->assertOk()->assertSee('no está habilitada');

        $full = $this->quickTournament(['max_participants' => 1]);
        $full->players()->attach(Player::factory()->create(), ['source' => 'manual', 'registered_at' => now()]);
        $this->get(route('quick-registrations.create', $full))->assertOk()->assertSee('Todos los cupos');

        $teams = $this->quickTournament(['participant_type' => ParticipantType::Team]);
        $this->get(route('quick-registrations.create', $teams))->assertOk()->assertSee('torneos individuales');
    }

    public function test_public_form_opens_during_extraordinary_period(): void
    {
        $tournament = $this->quickTournament([
            'status' => TournamentStatus::InProgress,
            'extraordinary_registration_enabled' => true,
            'registration_ends_at' => now()->subHour(),
        ]);

        $this->get(route('quick-registrations.create', $tournament))->assertOk()->assertSee('Confirmar inscripción');
        $this->post(route('quick-registrations.store', $tournament), [
            'full_name' => 'Ingreso Extraordinario',
            'username' => 'ExtraFC',
            'academic_level' => '11',
            'controller_platform' => 'ps5',
            'bring_own_controller' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('players', ['nickname' => 'ExtraFC']);
    }

    public function test_finished_tournament_closes_public_form_even_when_extraordinary_registration_was_enabled(): void
    {
        $tournament = $this->quickTournament([
            'status' => TournamentStatus::Finished,
            'extraordinary_registration_enabled' => true,
        ]);

        $this->get(route('quick-registrations.create', $tournament))
            ->assertOk()
            ->assertSee('Este torneo ya finalizó')
            ->assertDontSee('Confirmar inscripción');

        $this->post(route('quick-registrations.store', $tournament), [
            'full_name' => 'Ingreso Tardío',
            'username' => 'TardioFC',
            'academic_level' => '11',
            'controller_platform' => 'ps5',
            'bring_own_controller' => '1',
        ])->assertSessionHasErrors('registration');

        $this->assertDatabaseMissing('players', ['nickname' => 'TardioFC']);
    }

    public function test_public_routes_are_rate_limited(): void
    {
        $this->assertContains('throttle:10,1', Route::getRoutes()->getByName('quick-registrations.store')->gatherMiddleware());
        $this->assertContains('throttle:60,1', Route::getRoutes()->getByName('quick-registrations.create')->gatherMiddleware());
    }

    public function test_removing_public_registration_cleans_up_minimal_player(): void
    {
        $tournament = $this->quickTournament();
        $this->post(route('quick-registrations.store', $tournament), [
            'full_name' => 'Jugador Temporal',
            'username' => 'TemporalFC',
            'academic_level' => '11',
            'controller_platform' => 'ps4',
            'bring_own_controller' => '1',
        ]);
        $player = Player::query()->where('nickname', 'TemporalFC')->firstOrFail();

        $this->actingAs($this->administrator())
            ->delete(route('tournaments.registrations.destroy', [$tournament, $player->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('players', ['id' => $player->id]);
        $this->assertDatabaseMissing('tournament_players', ['player_id' => $player->id]);
    }

    private function quickTournament(array $attributes = [])
    {
        return $this->registrationTournament(attributes: [
            'quick_registration_enabled' => true,
            'quick_registration_levels' => ['11', '12'],
            'quick_registration_notice' => 'Debes traer tu propio control.',
            ...$attributes,
        ]);
    }
}
