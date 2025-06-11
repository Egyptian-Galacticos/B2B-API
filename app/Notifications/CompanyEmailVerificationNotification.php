<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyEmailVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public string $token;
    public string $verificationUrl;

    public function __construct(string $token)
    {
        $this->token = $token;
        $this->verificationUrl = config('app.frontend_url').'/auth/company-send?token='.$token;

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
            ->subject('Verify Your Company Email Address - '.config('app.name'))
            ->greeting('Hello!')
            ->line('Please click the button below to verify your company email address.')
            ->action('Verify Company Email', $this->verificationUrl)
            ->line('This verification link will expire in '.config('auth.verification.expire', 60).' minutes.')
            ->line('If you did not request this verification, no further action is required.')
            ->salutation('Best regards, '.config('app.name').' Team');
    }

    public function toArray($notifiable): array
    {
        return [
            'token'            => $this->token,
            'verification_url' => $this->verificationUrl,
        ];
    }
}
