<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyRegistration extends Notification
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
                ->line('Confirm your email address to finish registration.');
        }

        $minutes = (int) config('auth.verification.expire', 5);
        $firm = $notifiable->organization->name;

        return (new MailMessage)
            ->subject('Verify your '.$firm.' firm')
            ->greeting('Hello '.$notifiable->name)
            ->line('You registered '.$firm.' on '.config('app.name').'.')
            ->line('Confirm your email within '.$minutes.' minutes to open the firm.')
            ->action('Verify email', $notifiable->emailVerificationUrl())
            ->line('If you did not register, you can ignore this email.');
    }
}
