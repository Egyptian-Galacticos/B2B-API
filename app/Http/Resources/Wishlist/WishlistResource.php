<?php

namespace App\Http\Resources\Wishlist;

use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->pivot->id ?? null,
            'product' => ProductResource::make($this),
        ];
    }
}
