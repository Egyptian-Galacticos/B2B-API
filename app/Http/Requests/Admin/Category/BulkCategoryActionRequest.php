<?php

namespace App\Http\Requests\Admin\Category;

use Illuminate\Foundation\Http\FormRequest;

class BulkCategoryActionRequest extends FormRequest
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
            'category_ids'   => 'required|array|min:1|max:100',
            'category_ids.*' => 'required|integer|exists:categories,id',
            'action'         => 'required|string|in:activate,deactivate,delete,restore,force_delete',
            'reason'         => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'category_ids.required'   => 'Category IDs are required.',
            'category_ids.array'      => 'Category IDs must be an array.',
            'category_ids.min'        => 'At least one category ID is required.',
            'category_ids.max'        => 'Cannot process more than 100 categories at once.',
            'category_ids.*.required' => 'Each category ID is required.',
            'category_ids.*.integer'  => 'Each category ID must be an integer.',
            'category_ids.*.exists'   => 'One or more selected categories do not exist.',
            'action.required'         => 'Action is required.',
            'action.string'           => 'Action must be a string.',
            'action.in'               => 'Action must be one of: activate, deactivate, delete, restore, force_delete.',
            'reason.string'           => 'Reason must be a string.',
            'reason.max'              => 'Reason cannot exceed 500 characters.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'category_ids' => 'category IDs',
            'action'       => 'action',
            'reason'       => 'reason',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $action = $this->input('action');
            $categoryIds = $this->input('category_ids', []);

            // Additional validation based on action type
            switch ($action) {
                case 'force_delete':
                    // Only allow force delete on trashed categories
                    $trashedCount = \App\Models\Category::onlyTrashed()
                        ->whereIn('id', $categoryIds)
                        ->count();

                    if ($trashedCount !== count($categoryIds)) {
                        $validator->errors()->add('action', 'Force delete can only be performed on trashed categories.');
                    }
                    break;

                case 'restore':
                    // Only allow restore on trashed categories
                    $trashedCount = \App\Models\Category::onlyTrashed()
                        ->whereIn('id', $categoryIds)
                        ->count();

                    if ($trashedCount !== count($categoryIds)) {
                        $validator->errors()->add('action', 'Restore can only be performed on trashed categories.');
                    }
                    break;

                case 'delete':
                    // Check if categories can be deleted (no children or products)
                    $categoriesWithDependencies = \App\Models\Category::whereIn('id', $categoryIds)
                        ->where(function ($query) {
                            $query->whereHas('children')
                                ->orWhereHas('products');
                        })
                        ->count();

                    if ($categoriesWithDependencies > 0) {
                        $validator->errors()->add('action', 'Some categories cannot be deleted because they have children or associated products.');
                    }
                    break;
            }
        });
    }
}
