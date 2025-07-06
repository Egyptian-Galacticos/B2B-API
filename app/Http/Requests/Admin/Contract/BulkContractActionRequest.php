<?php

namespace App\Http\Requests\Admin\Contract;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkContractActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contract_ids'   => 'required|array|min:1',
            'contract_ids.*' => 'required|integer|exists:contracts,id',
            'action'         => 'required|string|in:delete,update_status',
            'status'         => [
                'required_if:action,update_status',
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
            'contract_ids.required' => 'Contract IDs are required',
            'contract_ids.array'    => 'Contract IDs must be an array',
            'contract_ids.min'      => 'At least one contract ID is required',
            'contract_ids.*.exists' => 'One or more selected contracts do not exist',
            'action.required'       => 'Action is required',
            'action.in'             => 'Invalid action. Allowed values are: delete, update_status',
            'status.required_if'    => 'Status is required when action is update_status',
        ];
    }
}
