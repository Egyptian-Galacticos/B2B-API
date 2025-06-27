<?php

namespace App\Http\Requests\Contract;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
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
            'quote_id' => 'required|exists:quotes,id',
            // @example  All payments must be made in USD unless otherwise agreed
            'terms_and_conditions' => 'required|string|min:50|max:10000',
            // @example 2025-07-15T10:00:00Z
            'estimated_delivery' => 'nullable|date|after:today',

            'shipping_address'             => 'nullable',
            'shipping_address.street'      => 'nullable|string|max:255',
            'shipping_address.city'        => 'nullable|string|max:100',
            'shipping_address.state'       => 'nullable|string|max:100',
            'shipping_address.postal_code' => 'nullable|string|max:20',
            'shipping_address.country'     => 'nullable|string|max:100',

            'billing_address'             => 'nullable',
            'billing_address.street'      => 'nullable|string|max:255',
            'billing_address.city'        => 'nullable|string|max:100',
            'billing_address.state'       => 'nullable|string|max:100',
            'billing_address.postal_code' => 'nullable|string|max:20',
            'billing_address.country'     => 'nullable|string|max:100',

            'metadata'   => 'nullable|array',
            'metadata.*' => 'string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'quote_id.required'             => 'Quote is required.',
            'quote_id.exists'               => 'Selected quote does not exist.',
            'terms_and_conditions.required' => 'Terms and conditions are required.',
            'terms_and_conditions.min'      => 'Terms and conditions must be at least 50 characters.',
            'terms_and_conditions.max'      => 'Terms and conditions cannot exceed 10,000 characters.',
            'estimated_delivery.date'       => 'Estimated delivery must be a valid date.',
            'estimated_delivery.after'      => 'Estimated delivery must be after today. This field is optional for service contracts, ongoing supply agreements, or contracts without specific delivery requirements.',

            'shipping_address.street.string'      => 'Shipping street must be a valid string.',
            'shipping_address.street.max'         => 'Shipping street cannot exceed 255 characters.',
            'shipping_address.city.string'        => 'Shipping city must be a valid string.',
            'shipping_address.city.max'           => 'Shipping city cannot exceed 100 characters.',
            'shipping_address.state.string'       => 'Shipping state must be a valid string.',
            'shipping_address.state.max'          => 'Shipping state cannot exceed 100 characters.',
            'shipping_address.postal_code.string' => 'Shipping postal code must be a valid string.',
            'shipping_address.postal_code.max'    => 'Shipping postal code cannot exceed 20 characters.',
            'shipping_address.country.string'     => 'Shipping country must be a valid string.',
            'shipping_address.country.max'        => 'Shipping country cannot exceed 100 characters.',

            'billing_address.street.string'      => 'Billing street must be a valid string.',
            'billing_address.street.max'         => 'Billing street cannot exceed 255 characters.',
            'billing_address.city.string'        => 'Billing city must be a valid string.',
            'billing_address.city.max'           => 'Billing city cannot exceed 100 characters.',
            'billing_address.state.string'       => 'Billing state must be a valid string.',
            'billing_address.state.max'          => 'Billing state cannot exceed 100 characters.',
            'billing_address.postal_code.string' => 'Billing postal code must be a valid string.',
            'billing_address.postal_code.max'    => 'Billing postal code cannot exceed 20 characters.',
            'billing_address.country.string'     => 'Billing country must be a valid string.',
            'billing_address.country.max'        => 'Billing country cannot exceed 100 characters.',

            'metadata.array'    => 'Metadata must be a valid array/object.',
            'metadata.*.string' => 'Each metadata value must be a string.',
            'metadata.*.max'    => 'Each metadata value cannot exceed 1000 characters.',
        ];
    }
}
