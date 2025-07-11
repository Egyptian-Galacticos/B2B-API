<?php

namespace App\Http\Requests\Admin\Quote;

use Illuminate\Foundation\Http\FormRequest;

class AdminQuoteFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'          => 'nullable|string|in:sent,accepted,rejected',
            'buyer_id'        => 'nullable|integer|exists:users,id',
            'seller_id'       => 'nullable|integer|exists:users,id',
            'rfq_id'          => 'nullable|integer|exists:rfqs,id',
            'conversation_id' => 'nullable|integer|exists:conversations,id',
            'price_min'       => 'nullable|numeric|min:0',
            'price_max'       => 'nullable|numeric|min:0|gte:price_min',
            'date_from'       => 'nullable|date',
            'date_to'         => 'nullable|date|after_or_equal:date_from',
            'size'        => 'nullable|integer|min:1|max:100',
            'page'            => 'nullable|integer|min:1',
            'sort'            => 'nullable|string',
            'filter'          => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in'              => 'Status must be one of: sent, accepted, rejected',
            'buyer_id.exists'        => 'The selected buyer does not exist',
            'seller_id.exists'       => 'The selected seller does not exist',
            'rfq_id.exists'          => 'The selected RFQ does not exist',
            'conversation_id.exists' => 'The selected conversation does not exist',
            'price_max.gte'          => 'Maximum price must be greater than or equal to minimum price',
            'date_to.after_or_equal' => 'End date must be after or equal to start date',
            'size.max'           => 'Items per page cannot exceed 100',
        ];
    }
}
