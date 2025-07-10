<?php

namespace App\Http\Requests\Chat;

use App\Http\Requests\BaseRequest;

class SendMessageRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'content' => 'required|string|max:10000',
            'type'    => 'sometimes|string|in:text,image,file,rfq,quote,contract',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Message content is required.',
            'content.max'      => 'Message content cannot exceed 10,000 characters.',
            'type.in'          => 'Message type must be text, image, or file.',
        ];
    }
}
