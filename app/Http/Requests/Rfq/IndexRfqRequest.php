<?php

namespace App\Http\Requests\Rfq;

use Illuminate\Foundation\Http\FormRequest;

class IndexRfqRequest extends FormRequest
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
            'user_type' => 'nullable|in:buyer,seller',
            'size'      => 'nullable|integer|min:1|max:100',
            'page'      => 'nullable|integer|min:1',
            'status'    => 'nullable|string',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_type.in'     => 'The user type must be either "buyer" or "seller".',
            'size.integer'     => 'The size must be a number.',
            'size.min'         => 'The size must be at least 1.',
            'size.max'         => 'The size cannot exceed 100.',
            'page.integer'     => 'The page must be a number.',
            'page.min'         => 'The page must be at least 1.',
            'buyer_id.exists'  => 'The selected buyer does not exist.',
            'seller_id.exists' => 'The selected seller does not exist.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'user_type' => 'user type',
            'size'      => 'page size',
            'page'      => 'page number',
            'buyer_id'  => 'buyer',
            'seller_id' => 'seller',
        ];
    }
}
