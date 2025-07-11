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
                    Contract::STATUS_IN_PROGRESS,
                    Contract::STATUS_DELIVERED_AND_PAID,
                    Contract::STATUS_SHIPPED,
                    Contract::STATUS_DELIVERED,
                    Contract::STATUS_COMPLETED,
                    Contract::STATUS_CANCELLED,
                    Contract::STATUS_PENDING_PAYMENT_CONFIRMATION,
                    Contract::BUYER_PAYMENT_REJECTED,
                ]),
            ],
            'seller_transaction_id' => [
                'nullable',
                'string',
                'max:255',
                'required_if:status,' . Contract::STATUS_DELIVERED_AND_PAID,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required'                   => 'Status is required',
            'status.in'                         => 'Invalid status. Allowed values are: pending_approval, approved, pending_payment, in_progress, delivered_and_paid, shipped, delivered, completed, cancelled',
            'seller_transaction_id.required_if' => 'Seller transaction ID is required when status is delivered_and_paid',
            'seller_transaction_id.string'      => 'Seller transaction ID must be a string',
            'seller_transaction_id.max'         => 'Seller transaction ID must not exceed 255 characters',
        ];
    }
}
