<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    // App\Http\Resources\CompanyResource.php
    public function toArray($request)
    {
        return [
            'id'                      => $this->id,
            'name'                    => $this->name,
            'email'                   => $this->email,
            'tax_id'                  => $this->tax_id,
            'company_phone'           => $this->company_phone,
            'commercial_registration' => $this->commercial_registration,
            'website'                 => $this->website,
            'description'             => $this->description,
            'logo'                    => $this->getFirstMedia('logo') ? [
                'id'            => $this->getFirstMedia('logo')->id,
                'name'          => $this->getFirstMedia('logo')->name,
                'file_name'     => $this->getFirstMedia('logo')->file_name,
                'url'           => $this->getFirstMedia('logo')->getUrl(),
                'thumbnail_url' => $this->getFirstMedia('logo')->getUrl('thumb'),
                'size'          => $this->getFirstMedia('logo')->size,
                'mime_type'     => $this->getFirstMedia('logo')->mime_type,
            ] : null,
            'address'           => $this->address,
            'is_email_verified' => $this->is_email_verified,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
