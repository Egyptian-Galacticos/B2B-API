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
            'id'              => $this->id,
            'rfq_id'          => $this->rfq_id,
            'total_price'     => $this->total_price,
            'seller_message'  => $this->seller_message,
            'conversation_id' => $this->conversation_id,
            'seller'          => UserResource::make($this->whenLoaded('seller')),
            'buyer'           => UserResource::make($this->whenLoaded('buyer')),
            'status'          => $this->status,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,

            'rfq' => $this->when($this->rfq_id && $this->relationLoaded('rfq'), [
                'initial_quantity' => $this->rfq?->initial_quantity,
                'shipping_country' => $this->rfq?->shipping_country,
                'buyer_message'    => $this->rfq?->buyer_message,
                'status'           => $this->rfq?->status,
            ]),

            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id'            => $item->id,
                        'product_id'    => $item->product_id,
                        'product_name'  => $item->product?->name,
                        'product_brand' => $item->product?->brand,
                        'quantity'      => $item->quantity,
                        'unit_price'    => $item->unit_price,
                        'total_price'   => $item->quantity * $item->unit_price,
                        'notes'         => $item->notes ?? '',
                    ];
                });
            }),

        ];
    }
}
