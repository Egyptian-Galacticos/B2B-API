<?php

namespace App\Helpers;

use App\Traits\ApiResponse;

class JWTResponseHelper
{
    use ApiResponse;

    public function tokenExpired()
    {
        return $this->apiResponseErrors(
            'Token expired',
            [
                'error'      => 'Token has expired',
                'message'    => 'Your authentication token has expired. Please log in again.',
                'code'       => 'TOKEN_EXPIRED',
                'timestamp'  => now()->toISOString(),
                'suggestion' => 'Use the refresh token endpoint to get a new access token',
            ],
            401
        );
    }

    public function tokenInvalid()
    {
        return $this->apiResponseErrors(
            'Invalid token',
            [
                'error'      => 'Token is invalid',
                'message'    => 'The provided authentication token is invalid or malformed.',
                'code'       => 'TOKEN_INVALID',
                'timestamp'  => now()->toISOString(),
                'suggestion' => 'Please log in again to obtain a valid token',
            ],
            401
        );
    }

    public function tokenNotProvided()
    {
        return $this->apiResponseErrors(
            'Authentication failed',
            [
                'error'      => 'Token not provided',
                'message'    => 'Authentication token is required for this endpoint.',
                'code'       => 'TOKEN_NOT_PROVIDED',
                'timestamp'  => now()->toISOString(),
                'suggestion' => 'Include the Authorization header with Bearer token',
            ],
            401
        );
    }

    public function tokenMalformed()
    {
        return $this->apiResponseErrors(
            'Malformed token',
            [
                'error'      => 'Token format invalid',
                'message'    => 'The provided authentication token is malformed or corrupted.',
                'code'       => 'TOKEN_MALFORMED',
                'timestamp'  => now()->toISOString(),
                'suggestion' => 'Please log in again to obtain a valid token',
            ],
            401
        );
    }

    public function authenticationRequired()
    {
        return $this->apiResponseErrors(
            'Unauthorized',
            [
                'error'      => 'Authentication required',
                'message'    => 'You must be authenticated to access this resource.',
                'code'       => 'AUTHENTICATION_REQUIRED',
                'timestamp'  => now()->toISOString(),
                'suggestion' => 'Please provide a valid authentication token',
            ],
            401
        );
    }
}
