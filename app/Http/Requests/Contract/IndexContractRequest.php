<?php

namespace App\Http\Requests\Contract;

use Illuminate\Foundation\Http\FormRequest;

class IndexContractRequest extends FormRequest
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
            'status'    => 'nullable|string|in:pending_approval,approved,pending_payment,in_progress,shipped,delivered,completed,cancelled',
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
            'user_type.in'         => 'The user type must be either "buyer" or "seller".',
            'size.integer'         => 'The size must be a number.',
            'size.min'             => 'The size must be at least 1.',
            'size.max'             => 'The size cannot exceed 100.',
            'page.integer'         => 'The page must be a number.',
            'page.min'             => 'The page must be at least 1.',
            'status.in'            => 'The status must be a valid contract status.',
            'currency.in'          => 'The currency must be one of: USD, EUR, GBP, CAD.',
            'total_amount.numeric' => 'The total amount must be a number.',
            'total_amount.min'     => 'The total amount must be at least 0.',
            'quote_id.exists'      => 'The selected quote does not exist.',
            'buyer_id.exists'      => 'The selected buyer does not exist.',
            'seller_id.exists'     => 'The selected seller does not exist.',
            'contract_date.date'   => 'The contract date must be a valid date.',
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
            'quote_id'  => 'quote',
            'buyer_id'  => 'buyer',
            'seller_id' => 'seller',
        ];
    }
}
