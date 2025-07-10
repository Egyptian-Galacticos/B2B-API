<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseRequest;

class StoreProductRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'brand'                       => ['nullable', 'string', 'max:255'],
            'model_number'                => ['nullable', 'string', 'max:255'],
            'sku'                         => ['required', 'string', 'max:255'],
            'name'                        => ['required', 'string', 'max:255'],
            'description'                 => ['nullable', 'string'],
            'hs_code'                     => ['nullable', 'string', 'max:255'],
            'weight'                      => ['required', 'numeric', 'min:0'],
            'currency'                    => ['required', 'string', 'size:3'], // e.g., USD, EUR
            'origin'                      => ['nullable', 'string', 'max:255'],
            'category_id'                 => ['required'],
            'category'                    => ['sometimes'],
            'certifications'              => ['nullable'],
            'dimensions'                  => ['nullable'],
            'sample_available'            => ['boolean'],
            'sample_price'                => ['nullable', 'numeric', 'min:0'],
            'price_tiers'                 => ['required'],
            'price_tiers.*'               => ['required', 'array'],
            'price_tiers.*.from_quantity' => ['required', 'integer', 'min:1'],
            'price_tiers.*.to_quantity'   => ['required', 'integer', 'min:1'],
            'price_tiers.*.price'         => ['required', 'numeric', 'min:0'],
            'product_tags'                => ['required'],

            // File validation
            'main_image'       => ['nullable', 'image', 'max:10240'], // 10MB max
            'images'           => ['nullable', 'array'],
            'images.*'         => ['image', 'max:10240'], // 10MB max per image
            'documents'        => ['nullable', 'array'],
            'documents.*'      => ['file', 'mimes:pdf,doc,docx,xlsx,xls,csv', 'max:20480'], // 20MB max per document
            'specifications'   => ['nullable', 'array'],
            'specifications.*' => ['file', 'mimes:pdf,doc,docx,xlsx,xls,csv', 'max:20480'], // 20MB max per specification file
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sku.required'         => 'The SKU is required.',
            'sku.unique'           => 'The SKU has already been taken.',
            'name.required'        => 'The product name is required.',
            'price.required'       => 'The price is required.',
            'currency.required'    => 'The currency is required.',
            'category_id.required' => 'The category ID is required.',
            'is_active.boolean'    => 'The active status must be true or false.',
            'main_image.image'     => 'The main image must be a valid image file.',
            'main_image.max'       => 'The main image may not be greater than 10MB.',
            'images.array'         => 'Images must be an array.',
            'images.*.image'       => 'Each image must be a valid image file.',
            'images.*.max'         => 'Each image may not be greater than 10MB.',
            'documents.array'      => 'Documents must be an array.',
            'documents.*.file'     => 'Each document must be a valid file.',
            'documents.*.mimes'    => 'Documents must be of type PDF, DOC, DOCX, XLSX, XLS, or CSV.',
            'documents.*.max'      => 'Each document may not be greater than 20MB.',
        ];
    }
}
