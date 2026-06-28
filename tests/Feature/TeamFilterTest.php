<?php

namespace Tests\Feature;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_teams_can_be_filtered_by_name_and_status(): void
    {
        $user = $this->administrator();
        Team::factory()->create(['name' => 'Ticos Elite', 'is_active' => true]);
        Team::factory()->inactive()->create(['name' => 'Inactive Rivals']);

        $this->actingAs($user)->get(route('teams.index', [
            'search' => 'Ticos',
            'is_active' => '1',
        ]))->assertOk()->assertSee('Ticos Elite')->assertDontSee('Inactive Rivals');
    }
}
