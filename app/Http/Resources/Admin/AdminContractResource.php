<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\CompanyResource;
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

            'buyer' => $this->whenLoaded('buyer', function () {
                return [
                    'id'                => $this->buyer->id,
                    'first_name'        => $this->buyer->first_name,
                    'last_name'         => $this->buyer->last_name,
                    'full_name'         => $this->buyer->getFullNameAttribute(),
                    'email'             => $this->buyer->email,
                    'phone_number'      => $this->buyer->phone_number,
                    'is_email_verified' => $this->buyer->is_email_verified,
                    'status'            => $this->buyer->status,
                    'last_login_at'     => $this->buyer->last_login_at,
                    'roles'             => $this->buyer->roles->pluck('name'),
                    'company'           => $this->buyer->company ? CompanyResource::make($this->buyer->company) : null,
                    'created_at'        => $this->buyer->created_at,
                    'updated_at'        => $this->buyer->updated_at,
                ];
            }),

            'seller' => $this->whenLoaded('seller', function () {
                return [
                    'id'                => $this->seller->id,
                    'first_name'        => $this->seller->first_name,
                    'last_name'         => $this->seller->last_name,
                    'full_name'         => $this->seller->getFullNameAttribute(),
                    'email'             => $this->seller->email,
                    'phone_number'      => $this->seller->phone_number,
                    'is_email_verified' => $this->seller->is_email_verified,
                    'status'            => $this->seller->status,
                    'last_login_at'     => $this->seller->last_login_at,
                    'roles'             => $this->seller->roles->pluck('name'),
                    'company'           => $this->seller->company ? CompanyResource::make($this->seller->company) : null,
                    'created_at'        => $this->seller->created_at,
                    'updated_at'        => $this->seller->updated_at,
                ];
            }),

            'quote' => $this->whenLoaded('quote', function () {
                return [
                    'id'             => $this->quote->id,
                    'total_price'    => $this->quote->total_price,
                    'seller_message' => $this->quote->seller_message,
                    'status'         => $this->quote->status,
                    'rfq'            => $this->quote->rfq ? [
                        'id'               => $this->quote->rfq->id,
                        'initial_quantity' => $this->quote->rfq->initial_quantity,
                        'shipping_country' => $this->quote->rfq->shipping_country,
                        'buyer_message'    => $this->quote->rfq->buyer_message,
                        'status'           => $this->quote->rfq->status,
                    ] : null,
                ];
            }),

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
