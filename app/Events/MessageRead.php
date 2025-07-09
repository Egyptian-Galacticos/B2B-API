<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public Message $message;
    public int $readByUserId;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message, int $readByUserId)
    {
        $this->message = $message;
        $this->readByUserId = $readByUserId;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->message->conversation_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.read';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message_id'      => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'read_by_user_id' => $this->readByUserId,
            'read_at'         => now()->toISOString(),
        ];
    }
}
