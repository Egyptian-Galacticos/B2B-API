<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SellerRegistrationApprovedNotification extends Notification implements ShouldQueue
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
            ->subject('Seller Registration Approved - Welcome to B2B Platform!')
            ->greeting('Congratulations!')
            ->line("Your seller registration for {$this->company->name} has been approved.")
            ->line('You can now start selling on our B2B platform.')
            ->action('Access Your Dashboard', url('/dashboard'))
            ->line('Thank you for joining our platform!');

        if ($this->reason) {
            $mailMessage->line("Approval reason: {$this->reason}");
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
            'action'       => 'approved',
            'reason'       => $this->reason,
            'notes'        => $this->notes,
            'timestamp'    => now(),
        ];
    }
}
