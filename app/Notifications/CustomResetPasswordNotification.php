<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomResetPasswordNotification extends Notification
{
    use Queueable;
    public $token;
    public $email;

    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'B2B Platform');
        $resetUrl = env('FRONTEND_URL', config('app.frontendUrl'))."/auth/reset-password?token={$this->token}&email={$this->email}";
        $expiration = config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Reset Your Password')
            ->greeting('Hello '.$notifiable->first_name.'!')
            ->line('You requested a password reset for your account.')
            ->action('Reset Password', $resetUrl)
            ->line("This link will expire in {$expiration} minutes.")
            ->line('If you did not request this, please ignore this email.')
            ->salutation('Best regards, '.$appName);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'password_reset',
            'email'   => $this->email,
            'user_id' => $notifiable->id,
        ];
    }
}
