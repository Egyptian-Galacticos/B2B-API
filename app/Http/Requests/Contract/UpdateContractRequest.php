<?php

namespace App\Http\Requests\Contract;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContractRequest extends FormRequest
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
            'status' => [
                'nullable',
                'string',
                Rule::in(Contract::VALID_STATUSES),
            ],
            'estimated_delivery'   => 'nullable|date',
            'shipping_address'     => 'nullable|string',
            'billing_address'      => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'metadata'             => 'nullable|array',
            'buyer_transaction_id' => 'nullable|string|regex:/^[A-Z0-9]{10,25}$/|max:255',
            'shipment_url'         => 'nullable|string|url|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $validStatuses = implode(', ', Contract::VALID_STATUSES);

        return [
            'status.in'                   => "Invalid status. Allowed values are: {$validStatuses}.",
            'estimated_delivery.date'     => 'Estimated delivery must be a valid date.',
            'buyer_transaction_id.string' => 'Buyer transaction ID must be a string.',
            'buyer_transaction_id.regex'  => 'Buyer transaction ID must be 10-25 characters long and contain only uppercase letters and numbers.',
            'buyer_transaction_id.max'    => 'Buyer transaction ID must not exceed 255 characters.',
        ];
    }
}
