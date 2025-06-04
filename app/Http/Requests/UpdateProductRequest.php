<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
            'seller_id' => ['required', 'exists:users,id'],
            'sku'       => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($this->product),
            ],
            'name'                   => ['required', 'string', 'max:255'],
            'description'            => ['nullable', 'string'],
            'hs_code'                => ['nullable', 'string', 'max:255'],
            'price'                  => ['required', 'numeric', 'min:0'],
            'currency'               => ['required', 'string', 'size:3'],
            'minimum_order_quantity' => ['nullable', 'integer', 'min:1'],
            'lead_time_days'         => ['nullable', 'integer', 'min:0'],
            'origin'                 => ['nullable', 'string', 'max:255'],
            'category_id'            => ['required', 'exists:categories,id'],
            'specifications'         => ['nullable', 'array'],
            'certifications'         => ['nullable', 'array'],
            'dimensions'             => ['nullable', 'array'],
            'is_active'              => ['boolean'],
        ];
    }
}
