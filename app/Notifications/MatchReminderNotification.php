<?php

namespace App\Notifications;

use App\Models\GameMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class MatchReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly GameMatch $match, public readonly string $window) {}

    public function via(object $notifiable): array
    {
        $p = $notifiable->notificationPreference;

        return array_values(array_filter([$p?->email_enabled === false ? null : 'mail', $p?->database_enabled === false ? null : 'database']));
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => 'Partido próximo', 'message' => 'Tu partido comienza en '.$this->window.'.', 'url' => route('tournaments.show', $this->match->tournament)];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Recordatorio de partido MatchPoint')->line('Tu partido comienza en '.$this->window.'.')->action('Ver torneo', route('tournaments.show', $this->match->tournament));
    }
}
