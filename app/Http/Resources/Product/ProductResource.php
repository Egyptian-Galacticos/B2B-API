<?php

namespace App\Http\Resources\Product;

use App\Http\Resources\MediaResource;
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
            'id'           => $this->id,
            'brand'        => $this->brand,
            'model_number' => $this->model_number,
            'name'         => $this->name,
            'slug'         => $this->slug,
            'description'  => $this->description,
            'weight'       => $this->weight,
            'currency'     => $this->currency,
            'is_featured'  => $this->is_featured,
            'is_active'    => $this->is_active,
            'is_approved'  => $this->is_approved,
            // Use the scope result directly - much more efficient!
            'in_wishlist'      => (bool) ($this->in_wishlist ?? false),
            'sample_available' => $this->sample_available,
            'sample_price'     => $this->sample_price,
            'category'         => $this->whenLoaded('category', function () {
                return [
                    'name' => $this->category->name,
                    'id'   => $this->category->id,
                ];
            }),
            'tags' => $this->whenLoaded('tags', function () {
                return $this->tags->pluck('name');
            }),

            'tiers' => $this->whenLoaded('tiers', function () {
                return $this->tiers->map(function ($tier) {
                    return [
                        'from_quantity' => $tier->from_quantity,
                        'to_quantity'   => $tier->to_quantity,
                        'price'         => $tier->price,
                    ];
                });
            }),

            // Relationships
            'seller' => $this->whenLoaded('seller', function () {

                if ($this->seller->relationLoaded('company') && $this->seller->company) {
                    $sellerData['company'] = [
                        'name' => $this->seller->company->name,
                        'logo' => $this->seller->company->logo,
                    ];
                } else {
                    $sellerData['company'] = null;
                }

                return $sellerData;
            }),

            'main_image' => $this->getFirstMedia('main_image')
                ? MediaResource::make($this->getFirstMedia('main_image'))
                : null,

            // Product images (multiple)
            'images' => MediaResource::collection($this->getMedia('product_images')),

        ];
    }
}
