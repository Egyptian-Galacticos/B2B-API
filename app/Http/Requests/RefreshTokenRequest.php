<?php

namespace App\Http\Requests;

use App\Models\RefreshToken;

class RefreshTokenRequest extends BaseRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'refresh_token' => [
                'required',
                'string',
                'exists:refresh_tokens,token',
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'refresh_token.required' => 'A refresh token is required.',
            'refresh_token.exists'   => 'The provided refresh token is invalid or has expired.',
        ];
    }

    /**
     * Get the validation rules that apply after the initial validation.
     *
     * @return array<\Closure>
     */
    public function after(): array
    {
        return [
            function ($validator) {
                if ($validator->errors()->count() > 0) {
                    return;
                }

                $token = $this->input('refresh_token');
                $refreshToken = RefreshToken::where('token', $token)->first();

                if (! $refreshToken || ! $refreshToken->isActive() || $refreshToken->isExpired()) {
                    $validator->errors()->add(
                        'token',
                        'This refresh token has expired.'
                    );
                }
                if ($refreshToken && $refreshToken->revoked) {
                    $validator->errors()->add(
                        'refresh_token',
                        'This refresh token has been revoked.'
                    );
                }
            },
        ];
    }
}
