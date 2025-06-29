<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array for detailed admin view.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'first_name'        => $this->first_name,
            'last_name'         => $this->last_name,
            'full_name'         => $this->first_name.' '.$this->last_name,
            'email'             => $this->email,
            'phone_number'      => $this->phone_number,
            'status'            => $this->status,
            'is_email_verified' => $this->is_email_verified,
            'email_verified_at' => $this->email_verified_at?->format('Y-m-d H:i:s'),
            'last_login_at'     => $this->last_login_at?->format('Y-m-d H:i:s'),
            'created_at'        => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at'        => $this->updated_at->format('Y-m-d H:i:s'),

            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->pluck('name')->toArray();
            }),

            'company' => $this->whenLoaded('company', function () {
                return [
                    'id'                      => $this->company->id,
                    'name'                    => $this->company->name,
                    'email'                   => $this->company->email,
                    'tax_id'                  => $this->company->tax_id,
                    'commercial_registration' => $this->company->commercial_registration,
                    'company_phone'           => $this->company->company_phone,
                    'website'                 => $this->company->website,
                    'description'             => $this->company->description,
                    'address'                 => $this->company->address,
                    'is_email_verified'       => $this->company->is_email_verified,
                    'created_at'              => $this->company->created_at->format('Y-m-d H:i:s'),
                    'updated_at'              => $this->company->updated_at->format('Y-m-d H:i:s'),
                ];
            }),

            'products_summary' => $this->whenLoaded('products', function () {
                return [
                    'total_products'  => $this->products->count(),
                    'recent_products' => $this->products->map(function ($product) {
                        return [
                            'id'         => $product->id,
                            'name'       => $product->name,
                            'is_active'  => $product->is_active,
                            'created_at' => $product->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
                ];
            }),

            'account_stats' => [
                'registration_days_ago' => $this->created_at->diffInDays(now()),
                'last_login_days_ago'   => $this->last_login_at ? $this->last_login_at->diffInDays(now()) : null,
                'is_seller'             => $this->whenLoaded('roles', function () {
                    return $this->roles->contains('name', 'seller');
                }),
                'is_buyer' => $this->whenLoaded('roles', function () {
                    return $this->roles->contains('name', 'buyer');
                }),
                'is_admin' => $this->whenLoaded('roles', function () {
                    return $this->roles->contains('name', 'admin');
                }),
                'has_company' => ! is_null($this->company),
            ],
        ];
    }
}
