<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentMadeToSellerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private Contract $contract
    ) {
        $this->onQueue('emails');
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
        $this->contract->load(['buyer', 'seller']);

        $sellerName = $this->contract->seller->name ??
            ($this->contract->seller->first_name.' '.$this->contract->seller->last_name) ??
            $this->contract->seller->email ?? 'Seller';

        $buyerName = $this->contract->buyer->name ??
            ($this->contract->buyer->first_name.' '.$this->contract->buyer->last_name) ??
            $this->contract->buyer->email ?? 'Buyer';

        return (new MailMessage)
            ->subject('Payment Processed - '.($this->contract->contract_number ?? 'N/A'))
            ->greeting('Hello '.$sellerName.',')
            ->line('Great news! Your payment has been processed successfully!')
            ->line('Contract Number: '.($this->contract->contract_number ?? 'N/A'))
            ->line('Buyer: '.$buyerName)
            ->line('Payment Amount: '.($this->contract->currency ?? 'USD').' '.number_format($this->contract->total_amount ?? 0, 2))
            ->when($this->contract->seller_transaction_id, function ($message) {
                return $message->line('Your Transaction ID: '.$this->contract->seller_transaction_id);
            })
            ->line('The goods have been delivered and payment has been transferred to your account.')
            ->line('The contract is now complete. Thank you for your service!')
            ->action('View Contract Details', url('/contracts/'.$this->contract->id))
            ->line('Thank you for your business!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'contract_id'           => $this->contract->id,
            'contract_number'       => $this->contract->contract_number,
            'buyer_id'              => $this->contract->buyer_id,
            'seller_id'             => $this->contract->seller_id,
            'status'                => $this->contract->status,
            'total_amount'          => $this->contract->total_amount,
            'currency'              => $this->contract->currency,
            'buyer_transaction_id'  => $this->contract->buyer_transaction_id,
            'seller_transaction_id' => $this->contract->seller_transaction_id,
        ];
    }
}
