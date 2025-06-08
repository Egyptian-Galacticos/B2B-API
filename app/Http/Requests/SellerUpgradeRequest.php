<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SellerUpgradeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'tax_id'                  => 'required|string|max:255|unique:companies,tax_id',
            'commercial_registration' => 'required|string|max:255|unique:companies,commercial_registration',
            'website'                 => 'nullable|url|max:255',
            'description'             => 'nullable|string|max:65535',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'tax_id.required'                  => 'Tax ID is required to upgrade to seller.',
            'tax_id.unique'                    => 'This Tax ID is already registered with another company.',
            'commercial_registration.required' => 'Commercial registration number is required to upgrade to seller.',
            'commercial_registration.unique'   => 'This commercial registration number is already registered with another company.',
            'website.url'                      => 'Please provide a valid website URL.',
        ];
    }
}
