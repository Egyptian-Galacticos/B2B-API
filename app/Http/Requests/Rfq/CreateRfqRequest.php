<?php

namespace App\Http\Requests\Rfq;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class CreateRfqRequest extends FormRequest
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
            'seller_id' => [
                'required',
                'exists:users,id',
                'different:'.Auth::id(),
                function ($attribute, $value, $fail) {
                    $user = User::find($value);
                    if ($user && ! $user->hasRole('seller')) {
                        $fail('The selected user is not a valid seller.');
                    }
                },
            ],
            'initial_product_id' => 'required|exists:products,id',
            'initial_quantity'   => 'required|integer|min:1',
            'shipping_country'   => 'required|string|max:255',
            'shipping_address'   => 'required|string|max:500',
            'buyer_message'      => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'seller_id.required'          => 'Seller is required',
            'seller_id.exists'            => 'Selected seller does not exist',
            'seller_id.different'         => 'You cannot create an RFQ to yourself',
            'initial_product_id.required' => 'Product is required',
            'initial_product_id.exists'   => 'Selected product does not exist',
            'initial_quantity.required'   => 'Quantity is required',
            'initial_quantity.min'        => 'Quantity must be at least 1',
            'shipping_country.required'   => 'Shipping country is required',
            'shipping_address.required'   => 'Shipping address is required',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->toArray();

        $firstError = collect($errors)->flatten()->first();

        throw new HttpResponseException(
            $this->apiResponseErrors(
                'Validation failed',
                $errors,
                422
            )
        );
    }
}
