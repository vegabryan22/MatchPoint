<?php

namespace Tests\Feature;

use App\Enums\MatchStatus;
use App\Models\GameMatch;
use App\Models\Round;
use App\Models\Tournament;
use App\Models\User;
use App\Notifications\MatchReminderNotification;
use App\Services\MatchReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_manages_preferences_and_inbox(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('notifications.index'))->assertOk();
        $this->actingAs($user)->put(route('notifications.preferences'), ['email_enabled' => 0, 'database_enabled' => 1, 'match_reminders' => 1, 'results' => 0, 'champions' => 1])->assertRedirect();
        $this->assertDatabaseHas('notification_preferences', ['user_id' => $user->id, 'email_enabled' => 0]);
    }

    public function test_due_reminder_is_sent_once(): void
    {
        Notification::fake();
        $admin = $this->administrator();
        $t = Tournament::factory()->create(['created_by' => $admin->id]);
        $r = Round::factory()->create(['tournament_id' => $t->id]);
        GameMatch::factory()->create(['tournament_id' => $t->id, 'round_id' => $r->id, 'status' => MatchStatus::Pending, 'scheduled_at' => now()->addHour()]);
        $service = app(MatchReminderService::class);
        $this->assertSame(1, $service->sendDue());
        $this->assertSame(0, $service->sendDue());
        Notification::assertSentTo($admin, MatchReminderNotification::class);
    }
}
