<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
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

            // Role information
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->pluck('name')->toArray();
            }),

            // Company information (for sellers)
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id'                      => $this->company->id,
                    'name'                    => $this->company->name,
                    'email'                   => $this->company->email,
                    'tax_id'                  => $this->company->tax_id,
                    'commercial_registration' => $this->company->commercial_registration,
                    'website'                 => $this->company->website,
                    'created_at'              => $this->company->created_at->format('Y-m-d H:i:s'),
                ];
            }),

            // Admin-specific computed fields
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
        ];
    }
}
