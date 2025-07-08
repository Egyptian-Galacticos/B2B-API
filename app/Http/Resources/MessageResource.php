<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'conversation_id' => $this->conversation_id,
            'content'         => $this->content,
            'type'            => $this->type,
            'sent_at'         => $this->sent_at?->toISOString(),
            'created_at'      => $this->created_at->toISOString(),
            'is_read'         => $this->is_read,
            'sender_id'       => $this->sender_id,
            'sender'          => new UserResource($this->sender),
        ];
    }
}
