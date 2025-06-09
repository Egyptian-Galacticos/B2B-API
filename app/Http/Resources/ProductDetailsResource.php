<?php

namespace App\Http\Resources;

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
                return new UserResource($this->seller);
            }),
            'sku'                    => $this->sku,
            'name'                   => $this->name,
            'slug'                   => $this->slug,
            'description'            => $this->description,
            'hs_code'                => $this->hs_code,
            'price'                  => $this->price,
            'currency'               => $this->currency,
            'minimum_order_quantity' => $this->minimum_order_quantity,
            'lead_time_days'         => $this->lead_time_days,
            'origin'                 => $this->origin,
            'category_id'            => $this->category_id,
            'category'               => $this->whenLoaded('category', function () {
                return [
                    'id'   => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ];
            }),
            'specifications'   => $this->specifications,
            'certifications'   => $this->certifications,
            'dimensions'       => $this->dimensions,
            'is_active'        => $this->is_active,
            'is_approved'      => $this->is_approved,
            'is_featured'      => $this->is_featured,
            'sample_available' => $this->sample_available,
            'sample_price'     => $this->sample_price,

            // Main image (single)
            'main_image' => $this->getFirstMedia('main_image') ? [
                'id'            => $this->getFirstMedia('main_image')->id,
                'name'          => $this->getFirstMedia('main_image')->name,
                'file_name'     => $this->getFirstMedia('main_image')->file_name,
                'url'           => $this->getFirstMedia('main_image')->getUrl(),
                'thumbnail_url' => $this->getFirstMedia('main_image')->getUrl('thumb'),
                'size'          => $this->getFirstMedia('main_image')->size,
                'mime_type'     => $this->getFirstMedia('main_image')->mime_type,
            ] : null,

            // Product images (multiple)
            'images' => $this->getMedia('product_images')->map(function ($media) {
                return [
                    'id'            => $media->id,
                    'name'          => $media->name,
                    'file_name'     => $media->file_name,
                    'url'           => $media->getUrl(),
                    'thumbnail_url' => $media->getUrl('thumb'),
                    'size'          => $media->size,
                    'mime_type'     => $media->mime_type,
                ];
            }),

            // Product documents
            'documents' => $this->getMedia('product_documents')->map(function ($media) {
                return [
                    'id'                  => $media->id,
                    'name'                => $media->name,
                    'file_name'           => $media->file_name,
                    'url'                 => $media->getUrl(),
                    'size'                => $media->size,
                    'mime_type'           => $media->mime_type,
                    'human_readable_size' => $this->formatBytes($media->size),
                ];
            }),

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
                        'id'           => $tier->id,
                        'min_quantity' => $tier->min_quantity,
                        'max_quantity' => $tier->max_quantity,
                        'price'        => $tier->price,
                        'currency'     => $tier->currency,
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
