<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;
use Illuminate\Support\Facades\Auth;

class UpdatePasswordRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'current_password'          => ['required', 'string'],
            'new_password'              => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&\-_#.])[A-Za-z\d@$!%*?&\-_#.]{8,}$/', 'confirmed'],
            'new_password_confirmation' => ['required', 'string'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'current_password.required'          => 'The current password is required.',
            'current_password.string'            => 'The current password must be a string.',
            'new_password.required'              => 'The new password is required.',
            'new_password.string'                => 'The new password must be a string.',
            'new_password.min'                   => 'The new password must be at least 8 characters.',
            'new_password.regex'                 => 'The new password must contain at least one lowercase letter, one uppercase letter, one digit, and one special character (@$!%*?&-_#.).',
            'new_password.confirmed'             => 'The new password confirmation does not match.',
            'new_password_confirmation.required' => 'The password confirmation is required.',
            'new_password_confirmation.string'   => 'The password confirmation must be a string.',
        ];
    }
}
