<?php

namespace App\Http\Resources;

use App\Http\Resources\Product\ProductResource;
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
            'id'               => $this->id,
            'buyer'            => UserResource::make($this->whenLoaded('buyer')),
            'seller'           => UserResource::make($this->whenLoaded('seller')),
            'initial_product'  => ProductResource::make($this->whenLoaded('initialProduct')),
            'initial_quantity' => $this->initial_quantity,
            'shipping_country' => $this->shipping_country,
            'shipping_address' => $this->shipping_address,
            'buyer_message'    => $this->buyer_message,
            'status'           => $this->status,
            'date'             => $this->created_at,
            'updated_at'       => $this->updated_at,
            'quotes'           => $this->whenLoaded('quotes', function () {
                return $this->quotes->map(function ($quote) {
                    return [
                        'id'     => $quote->id,
                        'status' => $quote->status,
                    ];
                });
            }),
        ];
    }
}
