<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RfqResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'buyer_id'           => $this->buyer_id,
            'seller_id'          => $this->seller_id,
            'initial_product_id' => $this->initial_product_id,
            'initial_quantity'   => $this->initial_quantity,
            'shipping_country'   => $this->shipping_country,
            'shipping_address'   => $this->shipping_address,
            'buyer_message'      => $this->buyer_message,
            'status'             => $this->status,
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
            'quotes'             => $this->whenLoaded('quotes', fn () => [
                'id'     => $this->id,
                'status' => $this->status,
            ]),
        ];
    }
}
