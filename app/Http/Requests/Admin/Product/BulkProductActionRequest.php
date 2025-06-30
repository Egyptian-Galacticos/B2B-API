<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;

class BulkProductActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'product_ids'   => 'required|array|min:1|max:100',
            'product_ids.*' => 'required|integer|exists:products,id',
            'action'        => 'required|string|in:approve,reject,activate,deactivate,feature,unfeature',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'product_ids.required'   => 'Product IDs are required.',
            'product_ids.array'      => 'Product IDs must be an array.',
            'product_ids.min'        => 'At least one product ID is required.',
            'product_ids.max'        => 'Cannot process more than 100 products at once.',
            'product_ids.*.required' => 'Each product ID is required.',
            'product_ids.*.integer'  => 'Each product ID must be an integer.',
            'product_ids.*.exists'   => 'One or more selected products do not exist.',
            'action.required'        => 'Action is required.',
            'action.string'          => 'Action must be a string.',
            'action.in'              => 'Action must be one of: approve, reject, activate, deactivate, feature, unfeature.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'product_ids'   => 'product IDs',
            'product_ids.*' => 'product ID',
            'action'        => 'action',
        ];
    }
}
