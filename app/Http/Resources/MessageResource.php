<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'conversation_id' => $this->conversation_id,
            'content'         => $this->content,
            'type'            => $this->type,
            'sent_at'         => $this->sent_at?->toISOString(),
            'is_read'         => $this->is_read,
            'created_at'      => $this->created_at->toISOString(),
            'updated_at'      => $this->updated_at->toISOString(),

            // Sender information
            'sender' => $this->whenLoaded('sender', function () {
                return [
                    'id'         => $this->sender->id,
                    'first_name' => $this->sender->first_name,
                    'last_name'  => $this->sender->last_name,
                    'full_name'  => $this->sender->full_name,
                    'avatar_url' => $this->sender->getFirstMediaUrl('profile_image'),
                ];
            }),

            // Attachments (if any)
            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(function ($attachment) {
                    return [
                        'id'            => $attachment->id,
                        'filename'      => $attachment->filename,
                        'original_name' => $attachment->original_name,
                        'mime_type'     => $attachment->mime_type,
                        'size'          => $attachment->size,
                        'url'           => $attachment->url,
                    ];
                });
            }),

            // Helper fields
            'is_from_current_user' => $this->when($request->user(), function () use ($request) {
                return $this->sender_id === $request->user()->id;
            }),

            'time_ago' => $this->created_at->diffForHumans(),
        ];
    }
}
