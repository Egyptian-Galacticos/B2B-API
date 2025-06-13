<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateProfileCompanyRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->company !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $user = Auth::user();
        $isSellerWithActiveStatus = $user->hasRole('seller') && $user->status === 'active';

        $rules = [
            'name'             => ['sometimes', 'string', 'max:255'],
            'company_phone'    => ['sometimes', 'string', 'max:20', 'nullable'],
            'website'          => ['sometimes', 'url', 'nullable'],
            'description'      => ['sometimes', 'string', 'nullable'],
            'logo'             => ['sometimes', 'file', 'image', 'mimes:jpeg,png,jpg,svg', 'max:5120', 'nullable'],
            'address'          => ['sometimes', 'array', 'nullable'],
            'address.street'   => ['nullable', 'string', 'max:255'],
            'address.city'     => ['nullable', 'string', 'max:100'],
            'address.state'    => ['nullable', 'string', 'max:100'],
            'address.country'  => ['nullable', 'string', 'max:100'],
            'address.zip_code' => ['nullable', 'string', 'max:20'],
        ];

        if (! $user->company->is_email_verified) {
            $rules['email'] = ['sometimes', 'email', 'max:255', Rule::unique('companies', 'email')->ignore($user->company->id)];
        } else {
            $rules['email'] = ['prohibited'];
        }

        if ($isSellerWithActiveStatus) {
            $rules['tax_id'] = ['prohibited'];
            $rules['commercial_registration'] = ['prohibited'];
        } else {
            $rules['tax_id'] = ['sometimes', 'string', 'max:255', 'nullable'];
            $rules['commercial_registration'] = ['sometimes', 'string', 'max:255', 'nullable'];
        }

        return $rules;
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.string'       => 'The company name must be a string.',
            'name.max'          => 'The company name may not be greater than 255 characters.',
            'email.email'       => 'The company email must be a valid email address.',
            'email.max'         => 'The company email may not be greater than 255 characters.',
            'email.prohibited'  => 'The company email is already verified and cannot be changed.',
            'tax_id.prohibited' => 'The tax ID cannot change for sellers with active status, please contact support.',

            'company_phone.string'           => 'The company phone must be a string.',
            'company_phone.max'              => 'The company phone may not be greater than 20 characters.',
            'website.url'                    => 'The website must be a valid URL.',
            'description.string'             => 'The description must be a string.',
            'logo.file'                      => 'The logo must be a file.',
            'logo.image'                     => 'The logo must be an image file.',
            'logo.mimes'                     => 'The logo must be a file of type: jpeg, png, jpg, gif, svg.',
            'logo.max'                       => 'The logo file size may not be greater than 2MB.',
            'address.array'                  => 'The address must be a valid array.',
            'address.street.string'          => 'The street address must be a string.',
            'address.street.max'             => 'The street address may not be greater than 255 characters.',
            'address.city.string'            => 'The city must be a string.',
            'address.city.max'               => 'The city may not be greater than 100 characters.',
            'address.state.string'           => 'The state must be a string.',
            'address.state.max'              => 'The state may not be greater than 100 characters.',
            'address.country.string'         => 'The country must be a string.',
            'address.country.max'            => 'The country may not be greater than 100 characters.',
            'address.zip_code.string'        => 'The zip code must be a string.',
            'address.zip_code.max'           => 'The zip code may not be greater than 20 characters.',
            'tax_id.string'                  => 'The tax ID must be a string.',
            'tax_id.max'                     => 'The tax ID may not be greater than 255 characters.',
            'commercial_registration.string' => 'The commercial registration must be a string.',
            'commercial_registration.max'    => 'The commercial registration may not be greater than 255 characters.',
        ];
    }
}
