<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;

class AdminProductFilterRequest extends FormRequest
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
            'size'      => 'sometimes|integer|min:1|max:100',
            'page'      => 'sometimes|integer|min:1',
            'search'    => 'sometimes|string|max:255',
            'sort'      => 'sometimes|string|max:50',
            'direction' => 'sometimes|string|in:asc,desc',

            'name'        => 'sometimes|string|max:255',
            'brand'       => 'sometimes|string|max:255',
            'currency'    => 'sometimes|string|size:3',
            'is_active'   => 'sometimes|boolean',
            'is_approved' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'seller_id'   => 'sometimes|integer|exists:users,id',
            'category_id' => 'sometimes|integer|exists:categories,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'seller_id.exists'    => 'The selected seller does not exist.',
            'category_id.exists'  => 'The selected category does not exist.',
            'currency.size'       => 'Currency must be a 3-character code.',
            'is_active.boolean'   => 'Active status must be true or false.',
            'is_approved.boolean' => 'Approved status must be true or false.',
            'is_featured.boolean' => 'Featured status must be true or false.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'seller_id'   => 'seller',
            'category_id' => 'category',
            'is_active'   => 'active status',
            'is_approved' => 'approved status',
            'is_featured' => 'featured status',
        ];
    }
}
