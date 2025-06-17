<?php

namespace App\Http\Requests\Rfq;

use App\Models\Rfq;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRfqRequest extends FormRequest
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
                    Rfq::STATUS_SEEN,
                    Rfq::STATUS_IN_PROGRESS,
                    Rfq::STATUS_QUOTED,
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status is required.',
            'status.in'       => 'Invalid status. Allowed values are: seen, In Progress, Quoted.',
        ];
    }
}
