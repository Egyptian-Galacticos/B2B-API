<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public Conversation $conversation;

    /**
     * Create a new event instance.
     */
    public function __construct(Conversation $conversation)
    {
        $this->conversation = $conversation;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Broadcast to all conversation participants
        foreach ($this->conversation->participants as $participant) {
            $channels[] = new PrivateChannel('user.'.$participant->id.'.notifications');
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'conversation.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id'           => $this->conversation->id,
            'name'         => $this->conversation->name,
            'type'         => $this->conversation->type,
            'participants' => $this->conversation->participants->map(function ($participant) {
                return [
                    'id'         => $participant->id,
                    'first_name' => $participant->first_name,
                    'last_name'  => $participant->last_name,
                    'avatar_url' => $participant->getFirstMediaUrl('profile_image'),
                ];
            }),
            'created_at' => $this->conversation->created_at->toISOString(),
        ];
    }
}
