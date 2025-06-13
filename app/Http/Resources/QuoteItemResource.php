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
            'id'          => $this->id,
            'quote_id'    => $this->quote_id,
            'product_id'  => $this->product_id,
            'quantity'    => $this->quantity,
            'unit_price'  => $this->unit_price,
            'notes'       => $this->notes,
            'total_price' => $this->quantity * $this->unit_price,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
            'product'     => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
