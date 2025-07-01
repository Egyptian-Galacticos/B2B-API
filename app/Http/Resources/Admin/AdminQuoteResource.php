<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\CompanyResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminQuoteResource extends JsonResource
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
            'seller'          => $this->whenLoaded('seller', function () {
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
            'status'     => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'rfq' => $this->whenLoaded('rfq', function () {
                return [
                    'initial_quantity' => $this->rfq->initial_quantity,
                    'shipping_country' => $this->rfq->shipping_country,
                    'buyer_message'    => $this->rfq->buyer_message,
                    'status'           => $this->rfq->status,
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

            'contract' => $this->whenLoaded('contract', function () {
                return [
                    'id'                 => $this->contract->id,
                    'contract_number'    => $this->contract->contract_number,
                    'status'             => $this->contract->status,
                    'total_amount'       => $this->contract->total_amount,
                    'contract_date'      => $this->contract->contract_date,
                    'estimated_delivery' => $this->contract->estimated_delivery,
                ];
            }),
        ];
    }
}
