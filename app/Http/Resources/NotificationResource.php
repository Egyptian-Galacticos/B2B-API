<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'type'       => $this->data['type'] ?? null,
            'title'      => $this->data['title'] ?? null,
            'message'    => $this->data['message'] ?? null,
            'entity_id'  => $this->data['entity_id'] ?? null,
            'status'     => $this->data['status'] ?? null,
            'priority'   => $this->data['priority'] ?? null,
            'read_at'    => $this->read_at ?? null,
            'created_at' => $this->created_at ?? now()->toDateTimeString(),
        ];
    }
}
