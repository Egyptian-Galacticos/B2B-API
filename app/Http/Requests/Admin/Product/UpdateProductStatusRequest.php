<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductStatusRequest extends FormRequest
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
            'is_approved' => 'sometimes|boolean',
            'is_active'   => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'is_approved.boolean' => 'Approval status must be true or false.',
            'is_active.boolean'   => 'Active status must be true or false.',
            'is_featured.boolean' => 'Featured status must be true or false.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'is_approved' => 'approval status',
            'is_active'   => 'active status',
            'is_featured' => 'featured status',
        ];
    }
}
