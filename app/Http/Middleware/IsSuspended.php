<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsSuspended
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

        // Check if user is suspended
        if ($user->isSuspended()) {
            return $this->apiResponseErrors(
                'Account suspended',
                [
                    'error'     => 'Account suspended',
                    'message'   => 'Your account has been suspended. Please contact support for assistance.',
                    'status'    => 'suspended',
                    'suspended' => true,
                ],
                403
            );
        }

        // Check if user is inactive (for non-sellers)
        if (! $user->isActive() && $user->isSeller()) {
            return $this->apiResponseErrors(
                'Account inactive',
                [
                    'error'   => 'Account inactive',
                    'message' => 'Your account is currently inactive or awaiting approval. Please contact support for assistance.',
                    'status'  => $user->status,
                    'active'  => false,
                ],
                403
            );
        }

        return $next($request);
    }
}
