<?php

namespace App\Http\Requests\Admin\Rfq;

use Illuminate\Foundation\Http\FormRequest;

class AdminRfqFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'             => 'nullable|string|in:pending,seen,in_progress,quoted,rejected',
            'buyer_id'           => 'nullable|integer|exists:users,id',
            'seller_id'          => 'nullable|integer|exists:users,id',
            'initial_product_id' => 'nullable|integer|exists:products,id',
            'shipping_country'   => 'nullable|string|max:100',
            'date_from'          => 'nullable|date',
            'date_to'            => 'nullable|date|after_or_equal:date_from',
            'quantity_min'       => 'nullable|integer|min:1',
            'quantity_max'       => 'nullable|integer|min:1|gte:quantity_min',
            'size'           => 'nullable|integer|min:1|max:100',
            'page'               => 'nullable|integer|min:1',
            'sort'               => 'nullable|string',
            'filter'             => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in'                 => 'Status must be one of: pending, seen, in_progress, quoted, rejected',
            'buyer_id.exists'           => 'The selected buyer does not exist',
            'seller_id.exists'          => 'The selected seller does not exist',
            'initial_product_id.exists' => 'The selected product does not exist',
            'date_to.after_or_equal'    => 'End date must be after or equal to start date',
            'quantity_max.gte'          => 'Maximum quantity must be greater than or equal to minimum quantity',
            'size.max'              => 'Items per page cannot exceed 100',
        ];
    }
}
