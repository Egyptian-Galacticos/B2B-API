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

        if (! $user) {
            return $next($request);
        }

        $user->refresh();

        if (! $user->hasVerifiedEmail()) {
            return $this->apiResponseErrors(
                'Email verification required',
                [
                    'error'                 => 'Email not verified',
                    'message'               => 'You must verify your email before accessing this resource.',
                    'verification_required' => true,
                    'debug_info'            => [
                        'is_email_verified' => $user->is_email_verified,
                        'email_verified_at' => $user->email_verified_at,
                        'user_id'           => $user->id,
                    ],
                ],
                403
            );
        }

        return $next($request);
    }
}
