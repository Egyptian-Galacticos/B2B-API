<?php

namespace App\Notifications;

use App\Models\Quote; // Assuming the Quote model exists
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class QuoteStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public Quote $quote;
    public string $newStatus;
    public string $priority;
    public string $message;
    public string $title;
    public string $read_at;
    public string $created_at;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        Quote $quote,
        string $priority = 'medium'
    ) {
        $this->quote = $quote;
        $this->priority = $priority;

        // Extract newStatus and changedBy from the Quote model
        $this->newStatus = $this->quote->status ?? 'unknown'; // Assuming 'status' attribute exists

        $this->title = 'Quote '.Str::title($this->newStatus);

        // Directly define the message in the constructor
        $this->message = "Your quote #{$this->quote->id} has been {$this->newStatus}.";
        $this->read_at = null;
        $this->created_at = now()->toDateTimeString();

        $this->onQueue('default');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'quote_status_changed',
            'title'      => $this->title,
            'message'    => $this->message,
            'entity_id'  => $this->quote->id,
            'status'     => $this->newStatus,
            'priority'   => $this->priority,
            'read_at'    => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    /**
     * Get the broadcast event name.
     */
    public function broadcastType(): string
    {
        return 'quote.status.changed';
    }
}
