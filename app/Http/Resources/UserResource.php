<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'full_name'         => $this->getFullNameAttribute(),
            'email'             => $this->email,
            'phone_number'      => $this->phone_number,
            'is_email_verified' => $this->is_email_verified,
            'status'            => $this->status,
            'last_login_at'     => $this->last_login_at,
            'roles'             => $this->roles->pluck('name'),
            'company'           => $this->when($this->company, function () {
                return [
                    'id'                      => $this->company->id,
                    'name'                    => $this->company->name,
                    'email'                   => $this->company->email,
                    'tax_id'                  => $this->company->tax_id,
                    'company_phone'           => $this->company->company_phone,
                    'commercial_registration' => $this->company->commercial_registration,
                    'website'                 => $this->company->website,
                    'description'             => $this->company->description,
                    'logo'                    => $this->company->logo,
                    'address'                 => $this->company->address,
                    'is_email_verified'       => $this->company->is_email_verified,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
