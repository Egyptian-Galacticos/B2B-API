<?php

namespace App\Http\Requests\Admin\Contract;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContractStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    Contract::STATUS_PENDING_APPROVAL,
                    Contract::STATUS_APPROVED,
                    Contract::STATUS_PENDING_PAYMENT,
                    Contract::STATUS_PENDING_PAYMENT_CONFIRMATION,
                    Contract::BUYER_PAYMENT_REJECTED,
                    Contract::STATUS_IN_PROGRESS,
                    Contract::STATUS_VERIFY_SHIPMENT_URL,
                    Contract::STATUS_SHIPPED,
                    Contract::STATUS_DELIVERED,
                    Contract::STATUS_DELIVERED_AND_PAID,
                    Contract::STATUS_COMPLETED,
                    Contract::STATUS_CANCELLED,
                ]),
            ],
            'seller_transaction_id' => [
                'nullable',
                'string',
                'regex:/^[A-Z0-9]{10,25}$/',
                'max:255',
                'required_if:status,'.Contract::STATUS_DELIVERED_AND_PAID,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required'                   => 'Status is required',
            'status.in'                         => 'Invalid status. Allowed values are: pending_approval, approved, pending_payment, pending_payment_confirmation, buyer_payment_rejected, in_progress, verify_shipment_url, shipped, delivered, delivered_and_paid, completed, cancelled',
            'seller_transaction_id.required_if' => 'Seller transaction ID is required when status is delivered_and_paid',
            'seller_transaction_id.string'      => 'Seller transaction ID must be a string',
            'seller_transaction_id.regex'       => 'Seller transaction ID must be 10-25 characters long and contain only uppercase letters and numbers',
            'seller_transaction_id.max'         => 'Seller transaction ID must not exceed 255 characters',
        ];
    }
}
