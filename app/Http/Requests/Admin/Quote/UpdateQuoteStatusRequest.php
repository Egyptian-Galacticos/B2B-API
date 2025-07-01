<?php

namespace App\Http\Requests\Admin\Quote;

use App\Models\Quote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuoteStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    Quote::STATUS_SENT,
                    Quote::STATUS_ACCEPTED,
                    Quote::STATUS_REJECTED,
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status is required',
            'status.in'       => 'Invalid status. Allowed values are: sent, accepted, rejected',
        ];
    }
}
