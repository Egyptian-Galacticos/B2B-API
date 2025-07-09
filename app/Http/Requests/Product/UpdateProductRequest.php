<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseRequest;

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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sku'  => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'description'  => ['sometimes', 'required', 'string'],
            'brand'        => ['sometimes', 'required', 'string', 'max:255'],
            'model_number' => ['sometimes', 'required', 'string', 'max:255'],
            'origin'       => ['sometimes', 'required', 'string', 'max:255'],
            'hs_code'      => ['sometimes', 'required', 'string', 'max:255'],

            'category_id'      => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'is_active'        => ['sometimes', 'boolean'],
            'sample_available' => ['sometimes', 'boolean'],

            'sample_price' => ['nullable', 'numeric', 'min:0', 'required_if:sample_available,true'],

            'weight'   => ['sometimes', 'required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'required', 'string', 'size:3', 'uppercase'],

            'price_tiers'                 => ['sometimes', 'required'],
            'price_tiers.*.from_quantity' => ['required', 'integer', 'min:1'], // These remain required if `price_tiers` is sent
            'price_tiers.*.to_quantity'   => ['required', 'integer', 'min:1', 'gte:price_tiers.*.from_quantity'],
            'price_tiers.*.price'         => ['required', 'numeric', 'min:0'],

            // --- Related Data (Arrays) ---
            'certifications' => ['sometimes', 'nullable'],

            'dimensions' => ['sometimes', 'nullable'],

            'product_tags' => ['sometimes', 'nullable'],

            'main_image' => ['sometimes', 'required', 'image', 'max:10240'],

            'images'   => ['sometimes', 'required'],
            'images.*' => ['image', 'max:10240'],

            'documents'   => ['sometimes', 'nullable', 'array'],
            'documents.*' => ['file', 'mimes:pdf,doc,docx', 'max:20480'],

            'specifications'   => ['sometimes', 'required', 'array'],
            'specifications.*' => ['file', 'mimes:pdf,doc,docx,xlsx,xls,csv', 'max:20480'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'sku.unique'                    => 'The SKU provided is already in use by another product.',
            'category_id.exists'            => 'The selected category does not exist.',
            'sample_price.required_if'      => 'Sample price is required when sample availability is enabled.',
            'currency.size'                 => 'The currency must be a 3-letter code (e.g., USD, EUR).',
            'currency.uppercase'            => 'The currency code must be in uppercase.',
            'price_tiers.*.to_quantity.gte' => 'The "to quantity" in a price tier must be greater than or equal to the "from quantity".',
            'product_tags.*.exists'         => 'One or more provided product tags are invalid.',
            'main_image.max'                => 'The main image may not be larger than 10MB.',
            'images.*.max'                  => 'An uploaded image may not be larger than 10MB.',
            'documents.*.max'               => 'An uploaded document may not be larger than 20MB.',
            'documents.*.mimes'             => 'Documents must be of type PDF, DOC, or DOCX.',
            'specifications.*.max'          => 'An uploaded specification file may not be larger than 20MB.',
            'specifications.*.mimes'        => 'Specification files must be of type PDF, DOC, DOCX, XLSX, XLS, or CSV.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert currency to uppercase if provided and not empty
        if ($this->has('currency') && ! empty($this->currency)) {
            $this->merge([
                'currency' => strtoupper($this->currency),
            ]);
        }
    }
}
