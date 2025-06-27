<?php

namespace App\Http\Requests\Quote;

use App\Models\Quote;
use App\Traits\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateQuoteRequest extends FormRequest
{
    use ApiResponse;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
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
                Rule::in([
                    Quote::STATUS_SENT,
                    Quote::STATUS_ACCEPTED,
                    Quote::STATUS_REJECTED,
                ]),
            ],
            'seller_message'     => 'nullable|string|max:1000',
            'items'              => 'sometimes|array|min:1',
            'items.*.id'         => 'nullable|exists:quote_items,id', // Nullable for new items
            'items.*.product_id' => 'required_without:items.*.id|exists:products,id', // Required for new items
            'items.*.quantity'   => 'required_with:items|integer|min:1',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
            'items.*.notes'      => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in'                           => 'Status must be one of: sent, accepted, rejected',
            'items.array'                         => 'Quote items must be an array',
            'items.min'                           => 'At least one quote item is required when updating items',
            'items.*.id.exists'                   => 'Selected quote item does not exist',
            'items.*.product_id.required_without' => 'Product ID is required for new items',
            'items.*.product_id.exists'           => 'Selected product does not exist',
            'items.*.quantity.required_with'      => 'Quantity is required for each item',
            'items.*.quantity.min'                => 'Quantity must be at least 1',
            'items.*.unit_price.required_with'    => 'Unit price is required for each item',
            'items.*.unit_price.min'              => 'Unit price must be at least 0',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->apiResponseErrors(
                'Validation failed',
                $validator->errors()->toArray(),
                422
            )
        );
    }
}
