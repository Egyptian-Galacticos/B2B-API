<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentUser = auth()->user();
        $otherParticipant = $this->getOtherParticipant($currentUser->id);

        return [
            'id'                => $this->id,
            'type'              => $this->type,
            'title'             => $this->title,
            'is_active'         => $this->is_active,
            'last_activity_at'  => $this->last_activity_at?->toISOString(),
            'created_at'        => $this->created_at->toISOString(),
            'updated_at'        => $this->updated_at->toISOString(),
            'seller'            => new UserResource($this->seller),
            'buyer'             => new UserResource($this->buyer),
            'other_participant' => new UserResource($otherParticipant),
            'last_message'      => $this->lastMessage ? new MessageResource($this->lastMessage) : null,
            'unread_count'      => $this->messages()
                ->where('sender_id', '!=', $currentUser->id)
                ->where('is_read', false)
                ->count(),
        ];
    }
}
