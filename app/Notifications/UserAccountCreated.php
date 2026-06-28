<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class UserAccountCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu cuenta MatchPoint está lista')
            ->greeting("Hola, {$notifiable->name}")
            ->line('Un administrador creó tu cuenta en MatchPoint.')
            ->action('Ingresar a MatchPoint', route('login'))
            ->line('Si no esperabas esta cuenta, contacta al administrador del torneo.');
    }
}
