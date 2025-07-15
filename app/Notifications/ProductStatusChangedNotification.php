<?php

namespace App\Notifications;

use App\Models\Product; // Assuming the Product model exists
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ProductStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public Product $product;
    public string $newStatus;
    public string $priority;
    public string $message;
    public string $title;
    public ?string $read_at;
    public string $created_at;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        Product $product,
        string $priority = 'medium'
    ) {
        $this->product = $product;
        $this->priority = $priority;

        $this->newStatus = $this->product->is_approved ? 'approved' : 'rejected';
        if ($this->product->is_approved === null) {
            $this->newStatus = 'pending';
        }

        $this->title = 'Product '.Str::title($this->newStatus);
        $this->message = "Your product #{$this->product->id} has been {$this->newStatus}.";
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
            'type'       => 'product_status_changed',
            'title'      => $this->title,
            'message'    => $this->message,
            'entity_id'  => $this->product->id,
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
        return 'product.status.changed';
    }
}
