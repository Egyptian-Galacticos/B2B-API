<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'seller_id' => ['required', 'exists:users,id'],
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'hs_code' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'], // e.g., USD, EUR
            'minimum_order_quantity' => ['nullable', 'integer', 'min:1'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'origin' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'specifications' => ['nullable', 'array'],
            'certifications' => ['nullable', 'array'],
            'dimensions' => ['nullable', 'array'],
            'is_active' => ['boolean'],
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
            'seller_id.required' => 'The seller ID is required.',
            'sku.required' => 'The SKU is required.',
            'name.required' => 'The product name is required.',
            'price.required' => 'The price is required.',
            'currency.required' => 'The currency is required.',
            'category_id.required' => 'The category ID is required.',
            'is_active.boolean' => 'The active status must be true or false.',
        ];
    }
}
