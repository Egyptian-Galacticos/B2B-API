<?php

namespace App\Http\Requests\Quote;

use App\Traits\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class CreateQuoteRequest extends FormRequest
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
            'rfq_id'             => 'nullable|exists:rfqs,id',
            'conversation_id'    => 'nullable|exists:conversations,id', // For quotes created from chat
            'seller_message'     => 'nullable|string|max:1000',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes'      => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'rfq_id.exists'               => 'Selected RFQ does not exist',
            'total_price.required'        => 'Total price is required',
            'total_price.numeric'         => 'Total price must be a valid number',
            'items.required'              => 'Quote items are required',
            'items.min'                   => 'At least one quote item is required',
            'items.*.product_id.required' => 'Product is required for each item',
            'items.*.product_id.exists'   => 'Selected product does not exist',
            'items.*.quantity.required'   => 'Quantity is required for each item',
            'items.*.quantity.min'        => 'Quantity must be at least 1',
            'items.*.unit_price.required' => 'Unit price is required for each item',
            'items.*.unit_price.min'      => 'Unit price must be at least 0',
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
