<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\CompanyResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminRfqResource extends JsonResource
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
            'initial_quantity' => $this->initial_quantity,
            'shipping_country' => $this->shipping_country,
            'shipping_address' => $this->shipping_address,
            'buyer_message'    => $this->buyer_message,
            'status'           => $this->status,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,

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

            'initialProduct' => $this->whenLoaded('initialProduct', function () {
                return [
                    'id'          => $this->initialProduct->id,
                    'name'        => $this->initialProduct->name,
                    'brand'       => $this->initialProduct->brand,
                    'description' => $this->initialProduct->description,
                    'price'       => $this->initialProduct->price,
                ];
            }),

            'quotes' => $this->whenLoaded('quotes', function () {
                return $this->quotes->map(function ($quote) {
                    return [
                        'id'             => $quote->id,
                        'total_price'    => $quote->total_price,
                        'seller_message' => $quote->seller_message,
                        'status'         => $quote->status,
                        'created_at'     => $quote->created_at,
                        'updated_at'     => $quote->updated_at,
                    ];
                });
            }),
        ];
    }
}
