<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_changes_are_recorded_without_sensitive_values(): void
    {
        $actor = $this->administrator();

        $this->actingAs($actor);
        $user = User::factory()->create(['name' => 'Original']);
        $user->update(['name' => 'Updated', 'password' => 'Another!1234']);

        $log = AuditLog::query()->where('action', 'updated')->where('auditable_id', $user->id)->latest('id')->firstOrFail();

        $this->assertSame('Original', $log->old_values['name']);
        $this->assertSame('Updated', $log->new_values['name']);
        $this->assertArrayNotHasKey('password', $log->new_values);
    }
}
