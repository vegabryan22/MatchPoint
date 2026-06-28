<?php

namespace Tests\Feature;

use App\Enums\ControllerType;
use App\Enums\PlayerLevel;
use App\Models\AuditLog;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlayerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_update_toggle_and_delete_player_with_photo(): void
    {
        Storage::fake('public');
        $admin = $this->administrator();

        $this->actingAs($admin)->post(route('players.store'), [
            ...$this->validPlayerData(),
            'photo' => $this->fakeImage('first.png'),
        ])->assertRedirect();

        $player = Player::query()->where('nickname', 'Tico10')->firstOrFail();
        $firstPhoto = $player->photo_path;
        Storage::disk('public')->assertExists($firstPhoto);

        $this->actingAs($admin)->put(route('players.update', $player), [
            ...$this->validPlayerData(),
            'nickname' => 'Tico11',
            'photo' => $this->fakeImage('second.png'),
        ])->assertRedirect(route('players.show', $player));

        $player->refresh();
        Storage::disk('public')->assertMissing($firstPhoto);
        Storage::disk('public')->assertExists($player->photo_path);

        $this->actingAs($admin)->patch(route('players.status', $player))->assertRedirect();
        $this->assertFalse($player->refresh()->is_active);

        $lastPhoto = $player->photo_path;
        $this->actingAs($admin)->delete(route('players.destroy', $player))->assertRedirect(route('players.index'));

        $this->assertDatabaseMissing('players', ['id' => $player->id]);
        Storage::disk('public')->assertMissing($lastPhoto);
    }

    public function test_player_changes_are_audited(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->post(route('players.store'), $this->validPlayerData());
        $player = Player::query()->where('nickname', 'Tico10')->firstOrFail();

        $this->actingAs($admin)->patch(route('players.status', $player));

        $this->assertTrue(AuditLog::query()->where('auditable_type', $player->getMorphClass())->where('action', 'created')->exists());
        $this->assertTrue(AuditLog::query()->where('auditable_type', $player->getMorphClass())->where('action', 'updated')->exists());
    }

    public function test_unique_nickname_and_email_are_validated(): void
    {
        $admin = $this->administrator();
        Player::factory()->create(['nickname' => 'Tico10', 'email' => 'player@example.com']);

        $this->actingAs($admin)->post(route('players.store'), $this->validPlayerData())
            ->assertSessionHasErrors(['nickname', 'email']);
    }

    private function validPlayerData(): array
    {
        return [
            'name' => 'Valeria Vega',
            'nickname' => 'Tico10',
            'email' => 'player@example.com',
            'country' => 'Costa Rica',
            'preferred_controller' => ControllerType::PlayStation->value,
            'level' => PlayerLevel::Advanced->value,
            'is_active' => '1',
        ];
    }
}
