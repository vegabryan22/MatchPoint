<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TournamentRegistrationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_can_view_and_export_but_cannot_manage_registrations(): void
    {
        $user = User::factory()->create();
        $tournament = $this->registrationTournament();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $tournament->players()->attach($player, ['source' => 'manual', 'registered_at' => now()]);
        $file = UploadedFile::fake()->createWithContent('players.csv', "nickname,email\nA,a@example.com\n");

        $this->actingAs($user)->get(route('tournaments.registrations.index', $tournament))->assertOk();
        $this->actingAs($user)->get(route('tournaments.registrations.export.csv', $tournament))->assertOk();
        $this->actingAs($user)->post(route('tournaments.registrations.store', $tournament), ['participant_id' => $player->id])->assertForbidden();
        $this->actingAs($user)->post(route('tournaments.registrations.import', $tournament), ['file' => $file])->assertForbidden();
        $this->actingAs($user)->delete(route('tournaments.registrations.destroy', [$tournament, $player->id]))->assertForbidden();
    }
}
