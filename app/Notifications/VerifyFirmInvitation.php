<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyFirmInvitation extends Notification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if (! $notifiable instanceof User) {
            return (new MailMessage)
                ->subject('Verify your email address')
                ->line('Confirm your email address to join your firm.');
        }

        $notifiable->loadMissing(['organization', 'invitedBy']);

        $minutes = (int) config('auth.verification.expire', 5);
        $firm = $notifiable->organization->name;
        $inviter = $notifiable->invitedBy instanceof User
            ? $notifiable->invitedBy->name
            : 'A supervisor';

        return (new MailMessage)
            ->subject('You have been added to '.$firm)
            ->greeting('Hello '.$notifiable->name)
            ->line($inviter.' added you to '.$firm.' on '.config('app.name').'.')
            ->line('Confirm your email within '.$minutes.' minutes, then sign in with the password they gave you.')
            ->action('Verify email', $notifiable->emailVerificationUrl())
            ->line('If you were not expecting this, you can ignore this email.');
    }
}
