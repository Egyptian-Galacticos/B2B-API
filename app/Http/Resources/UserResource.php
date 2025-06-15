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
        $roles = $this->roles->pluck('name');

        if ($this->status !== 'active') {
            $roles = $roles->filter(fn ($role) => $role !== 'seller')->values();
        }

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
            'roles'             => $roles,
            'company'           => $this->whenLoaded('company', function () {
                return CompanyResource::make($this->company);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
