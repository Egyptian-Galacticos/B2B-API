<?php

namespace App\Http\Requests;

class UpdateProfileRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'first_name'    => ['sometimes', 'string', 'max:255'],
            'last_name'     => ['sometimes', 'string', 'max:255'],
            'phone_number'  => ['sometimes', 'string', 'max:20', 'nullable', 'regex:/^[\+]?[1-9][\d]{0,15}$/'],
            'profile_image' => ['sometimes', 'file', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048', 'nullable'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.string'   => 'The first name must be a string.',
            'first_name.max'      => 'The first name may not be greater than 255 characters.',
            'last_name.string'    => 'The last name must be a string.',
            'last_name.max'       => 'The last name may not be greater than 255 characters.',
            'phone_number.string' => 'The phone number must be a string.',
            'phone_number.max'    => 'The phone number may not be greater than 20 characters.',
            'phone_number.regex'  => 'Invalid phone number format.',
            'profile_image.file'  => 'The profile image must be a file.',
            'profile_image.image' => 'The profile image must be an image file.',
            'profile_image.mimes' => 'The profile image must be a file of type: jpeg, png, jpg, gif, svg.',
            'profile_image.max'   => 'The profile image file size may not be greater than 2MB.',
        ];
    }
}
