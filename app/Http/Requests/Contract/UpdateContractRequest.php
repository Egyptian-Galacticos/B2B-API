<?php

namespace App\Http\Requests\Contract;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
                'required',
                'string',
                Rule::in(Contract::VALID_STATUSES),
            ],
            'estimated_delivery'   => 'nullable|date',
            'shipping_address'     => 'nullable|string',
            'billing_address'      => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'metadata'             => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $validStatuses = implode(', ', Contract::VALID_STATUSES);

        return [
            'status.required'         => 'Status is required.',
            'status.in'               => "Invalid status. Allowed values are: {$validStatuses}.",
            'estimated_delivery.date' => 'Estimated delivery must be a valid date.',
        ];
    }
}
