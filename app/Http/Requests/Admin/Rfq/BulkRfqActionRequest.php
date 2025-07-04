<?php

namespace App\Http\Requests\Admin\Rfq;

use App\Models\Rfq;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkRfqActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rfq_ids'   => 'required|array|min:1',
            'rfq_ids.*' => 'required|integer|exists:rfqs,id',
            'action'    => 'required|string|in:delete,update_status',
            'status'    => [
                'required_if:action,update_status',
                'string',
                Rule::in([
                    Rfq::STATUS_PENDING,
                    Rfq::STATUS_SEEN,
                    Rfq::STATUS_IN_PROGRESS,
                    Rfq::STATUS_QUOTED,
                    Rfq::STATUS_REJECTED,
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'rfq_ids.required'   => 'RFQ IDs are required',
            'rfq_ids.array'      => 'RFQ IDs must be an array',
            'rfq_ids.min'        => 'At least one RFQ ID is required',
            'rfq_ids.*.exists'   => 'One or more selected RFQs do not exist',
            'action.required'    => 'Action is required',
            'action.in'          => 'Invalid action. Allowed values are: delete, update_status',
            'status.required_if' => 'Status is required when action is update_status',
        ];
    }
}
