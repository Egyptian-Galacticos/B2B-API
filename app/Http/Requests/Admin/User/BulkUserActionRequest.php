<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;

class BulkUserActionRequest extends FormRequest
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
        $rules = [
            'user_ids' => 'required|array|min:1|max:100',
            'action'   => 'required|string|in:suspend,activate,delete,restore',
            'reason'   => 'nullable|string|max:500',
        ];

        if ($this->input('action') === 'restore') {
            $rules['user_ids.*'] = 'required|integer|exists:users,id';
        } else {
            $rules['user_ids.*'] = 'required|integer|exists:users,id';
        }

        return $rules;
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'user_ids.required' => 'User IDs are required.',
            'user_ids.array'    => 'User IDs must be an array.',
            'user_ids.min'      => 'At least one user must be selected.',
            'user_ids.max'      => 'Cannot process more than 100 users at once.',
            'user_ids.*.exists' => 'One or more selected users do not exist.',
            'action.required'   => 'Action is required.',
            'action.in'         => 'Action must be one of: suspend, activate, delete, restore',
            'reason.max'        => 'Reason cannot exceed 500 characters.',
        ];
    }
}
