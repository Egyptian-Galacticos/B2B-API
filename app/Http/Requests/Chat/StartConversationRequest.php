<?php

namespace App\Http\Requests\Chat;

use App\Http\Requests\BaseRequest;
use Illuminate\Support\Facades\Auth;

class StartConversationRequest extends BaseRequest
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
        $userId = Auth::user()?->id;

        return [
            'user_id' => 'required|integer|exists:users,id'.($userId ? '|different:'.$userId : ''),
            'type'    => 'sometimes|string|in:direct,contract',
            'title'   => 'sometimes|string|max:255',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'user_id.required'  => 'Please specify the user to start a conversation with.',
            'user_id.exists'    => 'The specified user does not exist.',
            'user_id.different' => 'You cannot start a conversation with yourself.',
            'type.in'           => 'Conversation type must be either direct or contract.',
        ];
    }
}
