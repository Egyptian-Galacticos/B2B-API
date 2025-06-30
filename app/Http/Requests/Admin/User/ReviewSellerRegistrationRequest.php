<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;

class ReviewSellerRegistrationRequest extends FormRequest
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
            'action' => 'required|string|in:approve,reject',
            'reason' => 'nullable|string|max:500',
            'notes'  => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Review action is required.',
            'action.in'       => 'Action must be either approve or reject.',
            'reason.max'      => 'Reason cannot exceed 500 characters.',
            'notes.max'       => 'Notes cannot exceed 1000 characters.',
        ];
    }
}
