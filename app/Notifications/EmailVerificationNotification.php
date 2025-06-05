<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public string $token;
    public string $verificationUrl;

    public function __construct(string $token)
    {
        $this->token = $token;
        $this->verificationUrl = config('app.frontend_url').'/auth/verify-email?token='.$token;

        $this->onQueue('emails');
        $this->delay(now()->addSeconds(5));
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify Your Email Address - '.config('app.name'))
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Please click the button below to verify your email address.')
            ->action('Verify Email Address', $this->verificationUrl)
            ->line('This verification link will expire in '.config('auth.verification.expire', 60).' minutes.')
            ->line('If you did not create an account, no further action is required.')
            ->salutation('Best regards, '.config('app.name').' Team');
    }
}
