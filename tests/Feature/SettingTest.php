<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_update_settings(): void
    {
        $admin = $this->administrator();
        $setting = Setting::factory()->create(['key' => 'site_name', 'value' => 'Old']);

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'settings' => [$setting->key => 'MatchPoint Arena'],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => 'MatchPoint Arena']);
    }
}
