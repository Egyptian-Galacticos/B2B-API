<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;

class UserFilterRequest extends FormRequest
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
            'status'                 => 'nullable|string|in:active,pending,suspended',
            'role'                   => 'nullable|string|in:admin,seller,buyer',
            'is_email_verified'      => 'nullable|in:true,false,1,0',
            'registration_date_from' => 'nullable|date|before_or_equal:today',
            'registration_date_to'   => 'nullable|date|after_or_equal:registration_date_from|before_or_equal:today',
            'search'                 => 'nullable|string|max:255',
            'sort_by'                => 'nullable|string|in:first_name,last_name,email,created_at,last_login_at,status',
            'sort_direction'         => 'nullable|string|in:asc,desc',
            'size'                   => 'nullable|integer|min:1|max:100',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'status.in'                              => 'Status must be one of: active, pending, suspended',
            'role.in'                                => 'Role must be one of: admin, seller, buyer',
            'is_email_verified.in'                   => 'Email verified filter must be true, false, 1, or 0',
            'registration_date_from.before_or_equal' => 'Registration date from cannot be in the future',
            'registration_date_to.after_or_equal'    => 'Registration date to must be after or equal to registration date from',
            'registration_date_to.before_or_equal'   => 'Registration date to cannot be in the future',
            'sort_by.in'                             => 'Sort by must be one of: first_name, last_name, email, created_at, last_login_at, status',
            'sort_direction.in'                      => 'Sort direction must be asc or desc',
            'size.max'                               => 'Per page cannot exceed 100 items',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values if not provided
        $this->mergeIfMissing([
            'size'           => 10,
            'sort_by'        => 'created_at',
            'sort_direction' => 'desc',
        ]);
    }
}
