<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseRequest;

class BulkProductActionRequest extends BaseRequest
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
            'product_ids'   => ['required', 'array', 'min:1', 'max:100'], // Limit bulk operations
            'product_ids.*' => ['integer', 'exists:products,id'],
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_ids.required'  => 'Product IDs are required for bulk operations.',
            'product_ids.array'     => 'Product IDs must be provided as an array.',
            'product_ids.min'       => 'At least one product ID must be provided.',
            'product_ids.max'       => 'Cannot process more than 100 products at once.',
            'product_ids.*.integer' => 'Each product ID must be an integer.',
            'product_ids.*.exists'  => 'One or more product IDs do not exist.',
        ];
    }
}
