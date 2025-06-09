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
            'brand'                  => $this->brand,
            'model_number'           => $this->model_number,
            'name'                   => $this->name,
            'slug'                   => $this->slug,
            'description'            => $this->description,
            'price'                  => $this->price,
            'currency'               => $this->currency,
            'minimum_order_quantity' => $this->minimum_order_quantity,
            'lead_time_days'         => $this->lead_time_days,
            'is_featured'            => $this->is_featured,
            'sample_available'       => $this->sample_available,
            'sample_price'           => $this->sample_price,

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

        ];
    }
}
