<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'seller_id'              => $this->seller_id,
            'sku'                    => $this->sku,
            'name'                   => $this->name,
            'description'            => $this->description,
            'hs_code'                => $this->hs_code,
            'price'                  => $this->price,
            'currency'               => $this->currency,
            'minimum_order_quantity' => $this->minimum_order_quantity,
            'lead_time_days'         => $this->lead_time_days,
            'origin'                 => $this->origin,
            'category_id'            => $this->category_id,
            'specifications'         => $this->specifications,
            'certifications'         => $this->certifications,
            'dimensions'             => $this->dimensions,
            'is_active'              => $this->is_active,
            'created_at'             => $this->created_at,
            'updated_at'             => $this->updated_at,
        ];
    }
}
