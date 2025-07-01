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
                    Contract::STATUS_SHIPPED,
                    Contract::STATUS_DELIVERED,
                    Contract::STATUS_COMPLETED,
                    Contract::STATUS_CANCELLED,
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status is required',
            'status.in'       => 'Invalid status. Allowed values are: pending_approval, approved, pending_payment, in_progress, shipped, delivered, completed, cancelled',
        ];
    }
}
