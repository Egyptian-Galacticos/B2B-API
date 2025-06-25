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
            'id'                   => $this->id,
            'contract_number'      => $this->contract_number,
            'status'               => $this->status,
            'total_amount'         => $this->total_amount,
            'currency'             => $this->currency,
            'contract_date'        => $this->contract_date?->toISOString(),
            'estimated_delivery'   => $this->estimated_delivery?->toISOString(),
            'shipping_address'     => $this->shipping_address,
            'billing_address'      => $this->billing_address,
            'terms_and_conditions' => $this->terms_and_conditions,
            'metadata'             => $this->metadata,
            'created_at'           => $this->created_at?->toISOString(),
            'updated_at'           => $this->updated_at?->toISOString(),

            'buyer' => $this->whenLoaded('buyer', function () {
                return [
                    'id'      => $this->buyer->id,
                    'name'    => $this->buyer->first_name.' '.$this->buyer->last_name,
                    'email'   => $this->buyer->email,
                    'company' => $this->whenLoaded('buyer.company', function () {
                        return [
                            'id'   => $this->buyer->company->id,
                            'name' => $this->buyer->company->name,
                        ];
                    }),
                ];
            }),

            'seller' => $this->whenLoaded('seller', function () {
                return [
                    'id'      => $this->seller->id,
                    'name'    => $this->seller->first_name.' '.$this->seller->last_name,
                    'email'   => $this->seller->email,
                    'company' => $this->whenLoaded('seller.company', function () {
                        return [
                            'id'   => $this->seller->company->id,
                            'name' => $this->seller->company->name,
                        ];
                    }),
                ];
            }),

            'quote' => $this->whenLoaded('quote', function () {
                return [
                    'id'          => $this->quote->id,
                    'total_price' => $this->quote->total_price,
                    'status'      => $this->quote->status,
                    'accepted_at' => $this->quote->accepted_at?->toISOString(),
                ];
            }),

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
