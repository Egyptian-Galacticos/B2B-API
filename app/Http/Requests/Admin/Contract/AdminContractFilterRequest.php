<?php

namespace App\Http\Requests\Admin\Contract;

use Illuminate\Foundation\Http\FormRequest;

class AdminContractFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'          => 'nullable|string|in:pending_approval,approved,pending_payment,in_progress,shipped,delivered,completed,cancelled',
            'buyer_id'        => 'nullable|integer|exists:users,id',
            'seller_id'       => 'nullable|integer|exists:users,id',
            'quote_id'        => 'nullable|integer|exists:quotes,id',
            'contract_number' => 'nullable|string|max:50',
            'amount_min'      => 'nullable|numeric|min:0',
            'amount_max'      => 'nullable|numeric|min:0|gte:amount_min',
            'date_from'       => 'nullable|date',
            'date_to'         => 'nullable|date|after_or_equal:date_from',
            'delivery_from'   => 'nullable|date',
            'delivery_to'     => 'nullable|date|after_or_equal:delivery_from',
            'size'        => 'nullable|integer|min:1|max:100',
            'page'            => 'nullable|integer|min:1',
            'sort'            => 'nullable|string',
            'filter'          => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in'                  => 'Status must be one of: pending_approval, approved, pending_payment, in_progress, shipped, delivered, completed, cancelled',
            'buyer_id.exists'            => 'The selected buyer does not exist',
            'seller_id.exists'           => 'The selected seller does not exist',
            'quote_id.exists'            => 'The selected quote does not exist',
            'amount_max.gte'             => 'Maximum amount must be greater than or equal to minimum amount',
            'date_to.after_or_equal'     => 'End date must be after or equal to start date',
            'delivery_to.after_or_equal' => 'Delivery end date must be after or equal to delivery start date',
            'size.max'               => 'Items per page cannot exceed 100',
        ];
    }
}
