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
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'content'                 => 'required_without:attachments|string|max:10000',
            'type'                    => 'sometimes|string|in:text,image,file,rfq,quote,contract',
            'attachments'             => 'sometimes|array|max:5', // Max 5 files per message
            'attachments.*.file'      => 'required_with:attachments|file|max:10240', // 10MB max per file
            'attachments.*.file_name' => 'sometimes|string|max:255',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'content.required_without'         => 'Message content is required when no files are attached.',
            'content.max'                      => 'Message content cannot exceed 10,000 characters.',
            'type.in'                          => 'Message type must be text, image, file, rfq, quote, or contract.',
            'attachments.max'                  => 'You can upload a maximum of 5 files per message.',
            'attachments.*.file.required_with' => 'File is required when uploading attachments.',
            'attachments.*.file.file'          => 'The uploaded file is invalid.',
            'attachments.*.file.max'           => 'Each file cannot exceed 10MB.',
            'attachments.*.file_name.max'      => 'File name cannot exceed 255 characters.',
        ];
    }
}
