<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'product_id'    => $this->product_id,
            'product_name'  => $this->product?->name,
            'product_brand' => $this->product?->brand,
            'quantity'      => $this->quantity,
            'unit_price'    => $this->unit_price,
            'total_price'   => $this->quantity * $this->unit_price,
            'notes'         => $this->notes,
        ];
    }
}
