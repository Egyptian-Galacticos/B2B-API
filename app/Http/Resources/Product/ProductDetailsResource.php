<?php

namespace App\Http\Resources\Product;

use App\Http\Resources\MediaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailsResource extends JsonResource
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
            'seller_id'    => $this->seller_id,
            'seller'       => $this->whenLoaded('seller', function () {

                if ($this->seller->relationLoaded('company') && $this->seller->company) {
                    $sellerData['company'] = $this->seller->company;
                } else {
                    $sellerData['company'] = null;
                }

                return $sellerData;
            }),
            'sku'         => $this->sku,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'hs_code'     => $this->hs_code,
            'weight'      => $this->weight,
            'currency'    => $this->currency,

            'origin'      => $this->origin,
            'category_id' => $this->category_id,
            'category'    => $this->whenLoaded('category', function () {
                return [
                    'id'   => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ];
            }),
            'tags' => $this->whenLoaded('tags', function () {
                return $this->tags->pluck('name');
            }),
            'certifications'   => $this->certifications,
            'dimensions'       => $this->dimensions,
            'is_active'        => $this->is_active,
            'is_approved'      => $this->is_approved,
            'is_featured'      => $this->is_featured,
            'in_wishlist'      => (bool) ($this->in_wishlist ?? false),
            'sample_available' => $this->sample_available,
            'sample_price'     => $this->sample_price,

            // Main image (single)
            'main_image' => $this->getFirstMedia('main_image')
                ? MediaResource::make($this->getFirstMedia('main_image'))
                : null,

            // Product images (multiple)
            'images' => MediaResource::collection($this->getMedia('product_images')),

            // Product documents
            'documents'      => MediaResource::collection($this->getMedia('product_documents')),
            'specifications' => MediaResource::collection($this->getMedia('product_specifications')),

            // Counts for convenience
            'media_counts' => [
                'main_image' => $this->getMedia('main_image')->count(),
                'images'     => $this->getMedia('product_images')->count(),
                'documents'  => $this->getMedia('product_documents')->count(),
            ],

            // Product tiers if available
            'tiers' => $this->whenLoaded('tiers', function () {
                return $this->tiers->map(function ($tier) {
                    return [
                        'from_quantity' => $tier->from_quantity,
                        'to_quantity'   => $tier->to_quantity,
                        'price'         => $tier->price,
                    ];
                });
            }),
        ];
    }

    /**
     * Format bytes into human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
