<?php

namespace App\Notifications;

use App\Models\Rfq; // Assuming the RFQ model exists
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewRfqCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public Rfq $rfq;
    public string $priority;
    public string $message;
    public string $title;
    public ?string $read_at;
    public string $created_at;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        Rfq $rfq,
        string $priority = 'medium'
    ) {
        $this->rfq = $rfq;
        $this->priority = $priority;

        $this->title = 'New RFQ Created';
        $this->message = "A new RFQ #{$this->rfq->id} has been created.";
        $this->read_at = null;
        $this->created_at = now()->toISOString();

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
            'type'       => 'new_rfq_created',
            'title'      => $this->title,
            'message'    => $this->message,
            'entity_id'  => $this->rfq->id,
            'status'     => 'created',
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
        return 'rfq.created';
    }
}
