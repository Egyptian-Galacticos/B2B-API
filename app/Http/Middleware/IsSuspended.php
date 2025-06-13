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
    public function handle(Request $request, Closure $next, ...$allowedStatuses): Response
    {
        $user = auth()->user();

        // If no authenticated user, let auth middleware handle it
        if (! $user) {
            return $next($request);
        }

        // If no statuses specified, default to 'active'
        if (empty($allowedStatuses)) {
            $allowedStatuses = ['active'];
        }

        // Check if user's status is in allowed statuses
        if (! in_array($user->status, $allowedStatuses)) {
            return $this->getStatusErrorResponse($user->status);
        }

        return $next($request);
    }

    /**
     * Get appropriate error response based on user status
     */
    private function getStatusErrorResponse(string $status): Response
    {
        return match ($status) {
            'suspended' => $this->apiResponseErrors(
                'Account suspended',
                [
                    'error'   => 'Account suspended',
                    'message' => 'Your account has been suspended. Please contact support for assistance.',
                    'status'  => 'suspended',
                ],
                403
            ),
            'pending' => $this->apiResponseErrors(
                'Account pending approval',
                [
                    'error'   => 'Account pending',
                    'message' => 'Your account is pending approval. Please wait for admin approval or contact support.',
                    'status'  => 'pending',
                ],
                403
            ),
            'inactive' => $this->apiResponseErrors(
                'Account inactive',
                [
                    'error'   => 'Account inactive',
                    'message' => 'Your account is inactive. Please contact support for assistance.',
                    'status'  => 'inactive',
                ],
                403
            ),
            default => $this->apiResponseErrors(
                'Access denied',
                [
                    'error'   => 'Invalid account status',
                    'message' => 'Your account status does not allow access to this resource.',
                    'status'  => $status,
                ],
                403
            )
        };
    }
}
