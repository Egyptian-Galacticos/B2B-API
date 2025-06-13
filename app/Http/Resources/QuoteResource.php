<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'rfq_id'         => $this->rfq_id,
            'total_price'    => $this->total_price,
            'seller_message' => $this->seller_message,
            'status'         => $this->status,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
            'rfq'            => new RfqResource($this->whenLoaded('rfq')),
            'items'          => QuoteItemResource::collection($this->whenLoaded('items')),
            'is_pending'     => $this->isPending(),
            'is_in_progress' => $this->isInProgress(),
            'is_seen'        => $this->isSeen(),
            'is_accepted'    => $this->isAccepted(),
            'is_rejected'    => $this->isRejected(),
        ];
    }
}
