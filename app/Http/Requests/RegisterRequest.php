<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'user.password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&-])/', 'confirmed'],
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
            // @example https://www.techcorp.com
            'company.website' => ['nullable', 'url', 'max:255'],
            // @example A leading tech company
            'company.description' => ['nullable', 'string'],
            'company.logo'        => ['nullable', 'image', 'max:2048', 'mimes:jpeg,png,jpg'],
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
        ];
    }

    public function messages(): array
    {
        return [
            'roles.required' => 'Please select at least one role.',
            'roles.*.in'     => 'Each role must be either "seller" or "buyer".',

            // User messages
            'user.firstName.required' => 'First name is required.',
            'user.firstName.regex'    => 'First name can only contain letters and spaces.',
            'user.lastName.required'  => 'Last name is required.',
            'user.lastName.regex'     => 'Last name can only contain letters and spaces.',
            'user.email.required'     => 'User email is required.',
            'user.email.email'        => 'Invalid user email format.',
            'user.email.unique'       => 'This email is already registered.',
            'user.password.required'  => 'Password is required.',
            'user.password.regex'     => 'Password must include uppercase, lowercase, number, and special character.',
            'user.password.confirmed' => 'Password confirmation does not match.',
            'user.phoneNumber.regex'  => 'Invalid phone number format.',

            // Company messages
            'company.name.required_if'        => 'Company name is required for sellers.',
            'company.email.required_if'       => 'Company email is required for sellers.',
            'company.email.email'             => 'Invalid company email format.',
            'company.website.required_if'     => 'Website is required for sellers.',
            'company.website.url'             => 'Invalid website URL.',
            'company.description.required_if' => 'Company description is required.',
            'company.logo.url'                => 'Logo must be a valid URL.',

            'company.address.street.required'  => 'Street is required.',
            'company.address.city.required'    => 'City is required.',
            'company.address.country.required' => 'Country is required.',
        ];
    }

    public function attributes(): array
    {
        return [
            'roles'            => 'Roles',
            'user.firstName'   => 'First Name',
            'user.lastName'    => 'Last Name',
            'user.email'       => 'User Email',
            'user.password'    => 'Password',
            'user.phoneNumber' => 'Phone Number',

            'company.name'                   => 'Company Name',
            'company.email'                  => 'Company Email',
            'company.taxId'                  => 'Tax ID',
            'company.companyPhone'           => 'Company Phone',
            'company.commercialRegistration' => 'Commercial Registration',
            'company.website'                => 'Website',
            'company.description'            => 'Description',
            'company.logo'                   => 'Company Logo',

            'company.address.street'  => 'Street',
            'company.address.city'    => 'City',
            'company.address.state'   => 'State',
            'company.address.zipCode' => 'ZIP Code',
            'company.address.country' => 'Country',
        ];
    }
}
