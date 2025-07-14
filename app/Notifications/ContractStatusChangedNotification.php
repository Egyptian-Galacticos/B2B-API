<?php

namespace App\Notifications;

use App\Models\Contract; // Assuming the Contract model exists
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ContractStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public Contract $contract;
    public string $newStatus;
    public ?string $changedBy;
    public string $priority;
    public string $message;
    public string $title;
    public ?string $read_at;
    public string $created_at;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        Contract $contract,
        string $priority = 'medium'
    ) {
        $this->contract = $contract;
        $this->priority = $priority;

        // Extract newStatus and changedBy from the Contract model
        $this->newStatus = $this->contract->status ?? 'unknown'; // Assuming 'status' attribute exists
        $this->changedBy = $this->contract->changed_by ?? null; // Assuming 'changed_by' attribute exists, or is null

        $this->title = 'Contract '.Str::title($this->newStatus);

        // Directly define the message in the constructor
        $messagePart = "Your contract #{$this->contract->id} has been {$this->newStatus}.";
        $changedByPart = $this->changedBy ? " Changed by {$this->changedBy}." : '';
        $this->message = $messagePart.$changedByPart;
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
            'type'       => 'contract_status_changed',
            'title'      => $this->title,
            'message'    => $this->message,
            'entity_id'  => $this->contract->id,
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
        return 'contract.status.changed';
    }
}
