<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * User registration request validation.
 *
 * This handles validation for new user registration including
 * both buyer and seller account types with company information.
 *
 * @see \App\Http\Controllers\Api\v1\AuthController::register()
 */
class RegisterRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Anyone can register
    }

    public function rules(): array
    {
        return [
            // Roles
            'roles'   => ['required', 'array'],
            'roles.*' => ['string', Rule::in(['seller', 'buyer'])],

            // User Data
            'user' => ['required', 'array'],
            // @example Anas
            'user.first_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            // @example Alhaj
            'user.last_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            // @example john.doe@gmail.com
            'user.email' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            // @example StrongPassword123!
            'user.password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&\-_#.])[A-Za-z\d@$!%*?&\-_#.]{8,}$/', 'confirmed'],
            // @example StrongPassword123!
            'user.password_confirmation' => ['required', 'same:user.password'],
            // @example +1234567890
            'user.phone_number' => ['nullable', 'string', 'max:20', 'regex:/^[\+]?[1-9][\d]{0,15}$/'],
            // Company Data

            'company' => ['required', 'array'],
            // @example My Company
            'company.name' => ['required', 'string', 'max:255'],
            // @example info@techcorp.com
            'company.email' => ['required', 'email:rfc,dns', 'max:255'],
            // @example 123456789
            'company.tax_id' => ['required_if:roles,seller', 'string', 'max:255'],
            // @example +1234567890
            'company.company_phone' => ['required', 'string', 'max:25'],
            // @example 1234567890
            'company.commercial_registration' => ['required_if:roles,seller', 'string', 'max:255'],
            // Company Address
            'company.address' => ['required', 'array'],
            // @example 123 Tech Street
            'company.address.street' => ['required', 'string', 'max:255'],
            // @example Tech City
            'company.address.city' => ['required', 'string', 'max:255'],
            // @example CA
            'company.address.state' => ['nullable', 'string', 'max:255'],
            // @example USA
            'company.address.country' => ['required', 'string', 'max:255'],
            // @example 12345
            'company.address.zip_code' => ['nullable', 'string', 'max:20'],

            'company.tax_id_images'   => ['nullable', 'array'],
            'company.tax_id_images.*' => ['file', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],

            'company.commercial_registration_images'   => ['nullable', 'array'],
            'company.commercial_registration_images.*' => ['file', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'roles.required' => 'Please select at least one role.',
            'roles.*.in'     => 'Each role must be either "seller" or "buyer".',

            // User messages
            'user.first_name.required' => 'First name is required.',
            'user.first_name.regex'    => 'First name can only contain letters and spaces.',
            'user.last_name.required'  => 'Last name is required.',
            'user.last_name.regex'     => 'Last name can only contain letters and spaces.',
            'user.email.required'      => 'User email is required.',
            'user.email.email'         => 'Invalid user email format.',
            'user.email.unique'        => 'This email is already registered.',
            'user.password.required'   => 'Password is required.',
            'user.password.regex'      => 'Password must include at least one uppercase letter, one lowercase letter, one number, and one special character (@$!%*?&-).',
            'user.password.confirmed'  => 'Password confirmation does not match.',
            'user.phone_number.regex'  => 'Invalid phone number format.',

            // Company messages
            'company.name.required'                       => 'Company name is required.',
            'company.email.required'                      => 'Company email is required.',
            'company.email.email'                         => 'Invalid company email format.',
            'company.tax_id.required_if'                  => 'Tax ID is required for sellers.',
            'company.company_phone.required'              => 'Company phone number is required.',
            'company.commercial_registration.required_if' => 'Commercial registration is required for sellers.',

            'company.address.street.required'  => 'Street is required.',
            'company.address.city.required'    => 'City is required.',
            'company.address.country.required' => 'Country is required.',
        ];
    }

    /**
     * Get the validation attributes for the request.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'roles'             => 'Roles',
            'user.first_name'   => 'First Name',
            'user.last_name'    => 'Last Name',
            'user.email'        => 'User Email',
            'user.password'     => 'Password',
            'user.phone_number' => 'Phone Number',

            'company.name'                    => 'Company Name',
            'company.email'                   => 'Company Email',
            'company.phone_number'            => 'Company Phone Number',
            'company.tax_id'                  => 'Tax ID',
            'company.company_phone'           => 'Company Phone',
            'company.commercial_registration' => 'Commercial Registration',
            'company.website'                 => 'Website',
            'company.description'             => 'Description',
            'company.logo'                    => 'Company Logo',

            'company.address.street'   => 'Street',
            'company.address.city'     => 'City',
            'company.address.state'    => 'State',
            'company.address.zip_code' => 'ZIP Code',
            'company.address.country'  => 'Country',
        ];
    }
}
