<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SellerRegistrationRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private Company $company,
        private ?string $reason = null,
        private ?string $notes = null
    ) {
        $this->onQueue('seller-notifications');
        $this->delay(now()->addSeconds(5));
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->subject('Seller Registration Update - B2B Platform')
            ->greeting('Hello!')
            ->line('Thank you for your interest in becoming a seller on our B2B platform.')
            ->line("After reviewing your seller registration for {$this->company->name}, we are unable to approve your seller account at this time.")
            ->line('However, you can continue to use the platform as a buyer.')
            ->action('Access Your Dashboard', url('/dashboard'))
            ->line('If you have any questions, please contact our support team.');

        if ($this->reason) {
            $mailMessage->line("Reason: {$this->reason}");
        }

        if ($this->notes) {
            $mailMessage->line("Additional notes: {$this->notes}");
        }

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'company_id'   => $this->company->id,
            'company_name' => $this->company->name,
            'action'       => 'rejected',
            'reason'       => $this->reason,
            'notes'        => $this->notes,
            'timestamp'    => now(),
        ];
    }
}
