<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\QuoteResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminContractResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'contract_number'      => $this->contract_number,
            'status'               => $this->status,
            'total_amount'         => $this->total_amount,
            'currency'             => $this->currency,
            'contract_date'        => $this->contract_date,
            'estimated_delivery'   => $this->estimated_delivery,
            'shipping_address'     => $this->shipping_address,
            'billing_address'      => $this->billing_address,
            'terms_and_conditions' => $this->terms_and_conditions,
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,

            'buyer' => UserResource::make($this->whenLoaded('buyer')),

            'seller' => UserResource::make($this->whenLoaded('seller')),

            'quote' => QuoteResource::make($this->whenLoaded('quote')),

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
