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

    public function __construct(Conversation $conversation)
    {
        $this->conversation = $conversation;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->conversation->seller_id.'.notifications'),
            new PrivateChannel('user.'.$this->conversation->buyer_id.'.notifications'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id'     => $this->conversation->id,
            'type'   => $this->conversation->type,
            'title'  => $this->conversation->title,
            'seller' => [
                'id'         => $this->conversation->seller->id,
                'first_name' => $this->conversation->seller->first_name,
                'last_name'  => $this->conversation->seller->last_name,
                'full_name'  => $this->conversation->seller->full_name,
                'avatar_url' => $this->conversation->seller->getFirstMediaUrl('profile_image'),
            ],
            'buyer' => [
                'id'         => $this->conversation->buyer->id,
                'first_name' => $this->conversation->buyer->first_name,
                'last_name'  => $this->conversation->buyer->last_name,
                'full_name'  => $this->conversation->buyer->full_name,
                'avatar_url' => $this->conversation->buyer->getFirstMediaUrl('profile_image'),
            ],
            'created_at' => $this->conversation->created_at->toISOString(),
        ];
    }
}
