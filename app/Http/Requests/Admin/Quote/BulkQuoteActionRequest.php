<?php

namespace App\Http\Requests\Admin\Quote;

use App\Models\Quote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkQuoteActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quote_ids'   => 'required|array|min:1',
            'quote_ids.*' => 'required|integer|exists:quotes,id',
            'action'      => 'required|string|in:delete,update_status',
            'status'      => [
                'required_if:action,update_status',
                'string',
                Rule::in([
                    Quote::STATUS_SENT,
                    Quote::STATUS_ACCEPTED,
                    Quote::STATUS_REJECTED,
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'quote_ids.required' => 'Quote IDs are required',
            'quote_ids.array'    => 'Quote IDs must be an array',
            'quote_ids.min'      => 'At least one quote ID is required',
            'quote_ids.*.exists' => 'One or more selected quotes do not exist',
            'action.required'    => 'Action is required',
            'action.in'          => 'Invalid action. Allowed values are: delete, update_status',
            'status.required_if' => 'Status is required when action is update_status',
        ];
    }
}
