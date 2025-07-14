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
            new PrivateChannel('chat.'.$this->conversation->seller_id),
            new PrivateChannel('chat.'.$this->conversation->buyer_id),
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
                'logo'       => $this->conversation->seller->company?->getFirstMediaUrl('logo'),
            ],
            'buyer' => [
                'id'         => $this->conversation->buyer->id,
                'first_name' => $this->conversation->buyer->first_name,
                'last_name'  => $this->conversation->buyer->last_name,
                'full_name'  => $this->conversation->buyer->full_name,
                'logo'       => $this->conversation->buyer->company?->getFirstMediaUrl('logo'),
            ],
            'created_at' => $this->conversation->created_at->toISOString(),
        ];
    }
}
