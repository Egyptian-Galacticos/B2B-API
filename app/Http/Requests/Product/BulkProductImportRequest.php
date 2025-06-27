<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseRequest;

class BulkProductImportRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'products'                               => ['required', 'array'],
            'products.*.brand'                       => ['nullable', 'string', 'max:255'],
            'products.*.model_number'                => ['nullable', 'string', 'max:255'],
            'products.*.sku'                         => ['required', 'string', 'max:255'],
            'products.*.name'                        => ['required', 'string', 'max:255'],
            'products.*.description'                 => ['nullable', 'string'],
            'products.*.hs_code'                     => ['nullable', 'string', 'max:255'],
            'products.*.weight'                      => ['required', 'numeric', 'min:0'],
            'products.*.currency'                    => ['required', 'string', 'size:3'],
            'products.*.origin'                      => ['nullable', 'string', 'max:255'],
            'products.*.category_id'                 => ['required', 'integer'],
            'products.*.certifications'              => ['nullable', 'array'],
            'products.*.dimensions'                  => ['nullable', 'array'],
            'products.*.sample_available'            => ['boolean'],
            'products.*.sample_price'                => ['nullable', 'numeric', 'min:0'],
            'products.*.price_tiers'                 => ['required', 'array'],
            'products.*.price_tiers.*.from_quantity' => ['required', 'integer', 'min:1'],
            'products.*.price_tiers.*.to_quantity'   => ['required', 'integer', 'min:1'],
            'products.*.price_tiers.*.price'         => ['required', 'numeric', 'min:0'],
            'products.*.product_tags'                => ['required', 'array'],

            // Image/document URLs
            'products.*.main_image'       => ['nullable', 'url'],
            'products.*.images'           => ['nullable', 'array'],
            'products.*.images.*'         => ['url'],
            'products.*.documents'        => ['nullable', 'array'],
            'products.*.documents.*'      => ['url'],
            'products.*.specifications'   => ['nullable', 'array'],
            'products.*.specifications.*' => ['url'],
        ];
    }

    public function messages(): array
    {
        return [
            'products.required'               => 'The products array is required.',
            'products.*.sku.required'         => 'The SKU is required for each product.',
            'products.*.name.required'        => 'The name is required for each product.',
            'products.*.price.required'       => 'The price is required for each product.',
            'products.*.currency.required'    => 'The currency is required for each product.',
            'products.*.category_id.required' => 'The category ID is required for each product.',
            'products.*.main_image.url'       => 'The main image must be a valid URL.',
            'products.*.images.*.url'         => 'Each image must be a valid URL.',
            'products.*.documents.*.url'      => 'Each document must be a valid URL.',
        ];
    }
}
