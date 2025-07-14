<?php

namespace App\Notifications;

use App\Models\Rfq; // Assuming the RFQ model exists
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class RFQStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public Rfq $rfq;
    public string $newStatus;
    public ?string $changedBy;
    public string $priority;
    public string $message;
    public string $title;
    public string $type;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        RFQ $rfq,
        string $priority = 'medium'
    ) {
        $this->rfq = $rfq;
        $this->priority = $priority;

        // Extract newStatus and changedBy from the RFQ model
        $this->newStatus = $this->rfq->status ?? 'unknown'; // Assuming 'status' attribute exists

        $this->type = 'rfq_status_changed';
        $this->title = 'RFQ '.Str::title($this->newStatus);

        // Directly define the message in the constructor
        $messagePart = "Your RFQ #{$this->rfq->id} has been {$this->newStatus}.";
        $this->message = $this->changedBy ? " Changed by {$this->changedBy}." : '';

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
            'type'      => $this->type,
            'title'     => $this->title,
            'message'   => $this->message,
            'entity_id' => $this->rfq->id,
            'status'    => $this->newStatus,
            'priority'  => $this->priority,
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

    public function broadcastType(): string
    {
        return $this->type; // This will override the default class name
    }
}
