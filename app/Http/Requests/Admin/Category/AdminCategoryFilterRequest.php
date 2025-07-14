<?php

namespace App\Http\Requests\Admin\Category;

use Illuminate\Foundation\Http\FormRequest;

class AdminCategoryFilterRequest extends FormRequest
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
            'search'          => 'sometimes|string|max:255',
            'status'          => 'sometimes|string|in:active,inactive,pending',
            'level'           => 'sometimes|integer|min:0|max:10',
            'parent_id'       => 'sometimes|nullable|integer|exists:categories,id',
            'has_products'    => 'sometimes|boolean',
            'has_children'    => 'sometimes|boolean',
            'created_by'      => 'sometimes|integer|exists:users,id',
            'updated_by'      => 'sometimes|integer|exists:users,id',
            'sort'            => 'sometimes|string|in:id,name,slug,status,level,created_at,updated_at,products_count,children_count',
            'order'           => 'sometimes|string|in:asc,desc',
            'size'            => 'sometimes|integer|min:1|max:100',
            'page'            => 'sometimes|integer|min:1',
            'include_trashed' => 'sometimes|boolean',
            'created_at_from' => 'sometimes|date',
            'created_at_to'   => 'sometimes|date|after_or_equal:created_at_from',
            'updated_at_from' => 'sometimes|date',
            'updated_at_to'   => 'sometimes|date|after_or_equal:updated_at_from',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'search.string'                => 'Search term must be a string.',
            'search.max'                   => 'Search term cannot exceed 255 characters.',
            'status.in'                    => 'Status must be one of: active, inactive, pending.',
            'level.integer'                => 'Level must be an integer.',
            'level.min'                    => 'Level cannot be negative.',
            'level.max'                    => 'Level cannot exceed 10.',
            'parent_id.integer'            => 'Parent ID must be an integer.',
            'parent_id.exists'             => 'Selected parent category does not exist.',
            'has_products.boolean'         => 'Has products filter must be true or false.',
            'has_children.boolean'         => 'Has children filter must be true or false.',
            'created_by.integer'           => 'Created by must be an integer.',
            'created_by.exists'            => 'Selected creator does not exist.',
            'updated_by.integer'           => 'Updated by must be an integer.',
            'updated_by.exists'            => 'Selected updater does not exist.',
            'sort.in'                      => 'Sort field must be one of: id, name, slug, status, level, created_at, updated_at, products_count, children_count.',
            'order.in'                     => 'Order must be either asc or desc.',
            'size.integer'                 => 'Per page must be an integer.',
            'size.min'                     => 'Per page must be at least 1.',
            'size.max'                     => 'Per page cannot exceed 100.',
            'page.integer'                 => 'Page must be an integer.',
            'page.min'                     => 'Page must be at least 1.',
            'include_trashed.boolean'      => 'Include trashed must be true or false.',
            'created_at_from.date'         => 'Created from date must be a valid date.',
            'created_at_to.date'           => 'Created to date must be a valid date.',
            'created_at_to.after_or_equal' => 'Created to date must be after or equal to created from date.',
            'updated_at_from.date'         => 'Updated from date must be a valid date.',
            'updated_at_to.date'           => 'Updated to date must be a valid date.',
            'updated_at_to.after_or_equal' => 'Updated to date must be after or equal to updated from date.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('has_products')) {
            $this->merge([
                'has_products' => filter_var($this->has_products, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }

        if ($this->has('has_children')) {
            $this->merge([
                'has_children' => filter_var($this->has_children, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }

        if ($this->has('include_trashed')) {
            $this->merge([
                'include_trashed' => filter_var($this->include_trashed, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }
}
