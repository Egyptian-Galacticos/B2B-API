<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'contract_number'       => $this->contract_number,
            'status'                => $this->status,
            'total_amount'          => $this->total_amount,
            'currency'              => $this->currency,
            'contract_date'         => $this->contract_date?->toISOString(),
            'estimated_delivery'    => $this->estimated_delivery?->toISOString(),
            'shipping_address'      => $this->shipping_address,
            'billing_address'       => $this->billing_address,
            'terms_and_conditions'  => $this->terms_and_conditions,
            'created_at'            => $this->created_at?->toISOString(),
            'updated_at'            => $this->updated_at?->toISOString(),
            'buyer_transaction_id'  => $this->buyer_transaction_id,
            'seller_transaction_id' => $this->seller_transaction_id,
            'shipment_url'          => $this->shipment_url,
            'buyer'                 => UserResource::make($this->whenLoaded('buyer')),

            'seller' => UserResource::make($this->whenLoaded('seller')),

            'quote' => QuoteResource::make($this->whenLoaded('quote')),

            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id'             => $item->id,
                        'quantity'       => $item->quantity,
                        'unit_price'     => $item->unit_price,
                        'total_price'    => $item->total_price,
                        'specifications' => $item->specifications,
                        'product'        => $item->product ? [
                            'id'    => $item->product->id,
                            'name'  => $item->product->name,
                            'sku'   => $item->product->sku ?? null,
                            'brand' => $item->product->brand ?? null,
                        ] : null,
                    ];
                });
            }),
        ];
    }
}
