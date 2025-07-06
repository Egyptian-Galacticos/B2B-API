<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'type'             => $this->type,
            'title'            => $this->title,
            'is_active'        => $this->is_active,
            'last_activity_at' => $this->last_activity_at?->toISOString(),
            'created_at'       => $this->created_at->toISOString(),
            'updated_at'       => $this->updated_at->toISOString(),

            // Participants
            'seller' => $this->whenLoaded('seller', function () {
                return [
                    'id'           => $this->seller->id,
                    'first_name'   => $this->seller->first_name,
                    'last_name'    => $this->seller->last_name,
                    'full_name'    => $this->seller->full_name,
                    'avatar_url'   => $this->seller->getFirstMediaUrl('profile_image'),
                    'company_name' => $this->seller->company?->name,
                ];
            }),

            'buyer' => $this->whenLoaded('buyer', function () {
                return [
                    'id'           => $this->buyer->id,
                    'first_name'   => $this->buyer->first_name,
                    'last_name'    => $this->buyer->last_name,
                    'full_name'    => $this->buyer->full_name,
                    'avatar_url'   => $this->buyer->getFirstMediaUrl('profile_image'),
                    'company_name' => $this->buyer->company?->name,
                ];
            }),

            // Last message
            'last_message' => $this->whenLoaded('lastMessage', function () {
                return $this->lastMessage ? [
                    'id'      => $this->lastMessage->id,
                    'content' => $this->lastMessage->content,
                    'type'    => $this->lastMessage->type,
                    'sent_at' => $this->lastMessage->sent_at?->toISOString(),
                    'is_read' => $this->lastMessage->is_read,
                    'sender'  => $this->whenLoaded('lastMessage.sender', function () {
                        return [
                            'id'         => $this->lastMessage->sender->id,
                            'first_name' => $this->lastMessage->sender->first_name,
                            'last_name'  => $this->lastMessage->sender->last_name,
                        ];
                    }),
                ] : null;
            }),

            // Helper fields for current user
            'other_participant' => $this->when($request->user(), function () use ($request) {
                $currentUser = $request->user();
                if ($currentUser->id === $this->seller_id) {
                    return $this->whenLoaded('buyer', function () {
                        return [
                            'id'           => $this->buyer->id,
                            'first_name'   => $this->buyer->first_name,
                            'last_name'    => $this->buyer->last_name,
                            'full_name'    => $this->buyer->full_name,
                            'avatar_url'   => $this->buyer->getFirstMediaUrl('profile_image'),
                            'company_name' => $this->buyer->company?->name,
                        ];
                    });
                } elseif ($currentUser->id === $this->buyer_id) {
                    return $this->whenLoaded('seller', function () {
                        return [
                            'id'           => $this->seller->id,
                            'first_name'   => $this->seller->first_name,
                            'last_name'    => $this->seller->last_name,
                            'full_name'    => $this->seller->full_name,
                            'avatar_url'   => $this->seller->getFirstMediaUrl('profile_image'),
                            'company_name' => $this->seller->company?->name,
                        ];
                    });
                }

                return null;
            }),

            'unread_count' => $this->when($request->user(), function () use ($request) {
                return $this->messages()
                    ->where('sender_id', '!=', $request->user()->id)
                    ->where('is_read', false)
                    ->count();
            }),
        ];
    }
}
