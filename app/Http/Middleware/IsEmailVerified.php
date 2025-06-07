<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsEmailVerified
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // If no authenticated user, let auth middleware handle it
        if (! $user) {
            return $next($request);
        }

        // Check if user's email is verified
        if (! $user->is_email_verified) {
            return $this->apiResponseErrors(
                'Email verification required',
                [
                    'error'                 => 'Email not verified',
                    'message'               => 'You must verify your email before accessing this resource.',
                    'verification_required' => true,
                ],
                403
            );
        }

        return $next($request);
    }
}
