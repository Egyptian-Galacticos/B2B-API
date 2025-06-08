<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->isSeller() && auth()->user()->company;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $companyId = auth()->user()->company?->id;

        return [
            'name'  => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('companies', 'email')->ignore($companyId),
            ],
            'tax_id' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('companies', 'tax_id')->ignore($companyId),
            ],
            'commercial_registration' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('companies', 'commercial_registration')->ignore($companyId),
            ],
            'company_phone'    => 'nullable|string|max:255',
            'address'          => 'nullable|array',
            'address.street'   => 'nullable|string|max:255',
            'address.city'     => 'nullable|string|max:100',
            'address.state'    => 'nullable|string|max:100',
            'address.country'  => 'nullable|string|max:100',
            'address.zip_code' => 'nullable|string|max:20',
            'website'          => 'nullable|url|max:255',
            'description'      => 'nullable|string|max:65535',
            'logo'             => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required'                    => 'Company name is required.',
            'email.required'                   => 'Company email is required.',
            'email.email'                      => 'Please provide a valid email address.',
            'email.unique'                     => 'This email is already registered with another company.',
            'tax_id.required'                  => 'Tax ID is required.',
            'tax_id.unique'                    => 'This Tax ID is already registered with another company.',
            'commercial_registration.required' => 'Commercial registration number is required.',
            'commercial_registration.unique'   => 'This commercial registration number is already registered with another company.',
            'website.url'                      => 'Please provide a valid website URL.',
        ];
    }
}
