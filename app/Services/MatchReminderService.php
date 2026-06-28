<?php

namespace App\Services;

use App\Enums\MatchStatus;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\User;
use App\Notifications\MatchReminderNotification;
use Illuminate\Support\Facades\DB;

final class MatchReminderService
{
    public function sendDue(): int
    {
        $sent = 0;
        foreach (['24h' => 24, '1h' => 1] as $window => $hours) {
            $matches = GameMatch::query()->with('tournament')->where('status', MatchStatus::Pending)->whereBetween('scheduled_at', [now()->addHours($hours)->subMinutes(5), now()->addHours($hours)->addMinutes(5)])->get();
            foreach ($matches as $match) {
                $users = $this->recipients($match);
                foreach ($users as $user) {
                    $created = DB::table('match_reminders')->insertOrIgnore(['match_id' => $match->id, 'user_id' => $user->id, 'window' => $window, 'sent_at' => now()]);
                    if ($created) {
                        $user->notify(new MatchReminderNotification($match, $window));
                        $sent++;
                    }
                }
            }
        }

return $sent;
    }

    private function recipients(GameMatch $match)
    {
        $ids = collect([$match->tournament->created_by]);
        if ($match->participant_type->value === 'individual') {
            $ids->push(...Player::query()->whereIn('id', [$match->participant_a_id, $match->participant_b_id])->pluck('user_id')->filter());
        }

return User::query()->with('notificationPreference')->whereIn('id', $ids->filter()->unique())->where('is_active', true)->get()->filter(fn ($u) => $u->notificationPreference?->match_reminders !== false);
    }
}
