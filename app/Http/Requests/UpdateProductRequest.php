<?php

namespace App\Http\Requests;

class UpdateProductRequest extends BaseRequest
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
    public function rules(): array
    {
        return [
            'brand'        => ['nullable', 'string', 'max:255'],
            'model_number' => ['nullable', 'string', 'max:255'],
            'sku'          => [
                'required',
                'string',
                'max:255',
            ],
            'name'                        => ['required', 'string', 'max:255'],
            'description'                 => ['nullable', 'string'],
            'hs_code'                     => ['nullable', 'string', 'max:255'],
            'price'                       => ['required', 'numeric', 'min:0'],
            'currency'                    => ['required', 'string', 'size:3'],
            'origin'                      => ['nullable', 'string', 'max:255'],
            'category_id'                 => ['required', 'exists:categories,id'],
            'specifications'              => ['nullable', 'array'],
            'certifications'              => ['nullable', 'array'],
            'dimensions'                  => ['nullable', 'array'],
            'is_active'                   => ['boolean'],
            'sample_available'            => ['boolean'],
            'sample_price'                => ['nullable', 'numeric', 'min:0'],
            'price_tiers.*'               => ['required', 'array'],
            'price_tiers.*.from_quantity' => ['required', 'integer', 'min:1'],
            'price_tiers.*.to_quantity'   => ['required', 'integer', 'min:1'],
            'price_tiers.*.price'         => ['required', 'numeric', 'min:0'],

            // tags
            'product_tags' => ['sometimes', 'array'],

            // File validation
            //            'main_image'             => ['nullable', 'image', 'max:10240'], // 10MB max
            //            'images'                 => ['nullable', 'array'],
            //            'images.*'               => ['image', 'max:10240'], // 10MB max per image
            //            'documents'              => ['nullable', 'array'],
            //            'documents.*'            => ['file', 'mimes:pdf,doc,docx', 'max:20480'], // 20MB max per document
        ];
    }
}
