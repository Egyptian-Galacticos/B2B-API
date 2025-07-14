<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     * @param string ...$roles One or more required role names (comma-separated or multiple arguments)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return $this->apiResponseErrors(
                'Authentication Error',
                ['error' => 'User session not found'],
                401
            );
        }

        if (count($roles) === 1 && strpos($roles[0], ',') !== false) {
            $roles = explode(',', $roles[0]);
        }

        $roles = array_map('trim', $roles);

        $hasRequiredRole = $user->hasAnyRole($roles);

        if (! $hasRequiredRole) {
            return $this->apiResponseErrors(
                'Access Denied',
                [
                    'error' => count($roles) === 1
                        ? "Access denied. Required role: {$roles[0]}"
                        : 'Access denied. Required role(s): '.implode(' or ', $roles),
                    'user_roles'     => $user->roles->pluck('name')->toArray(),
                    'required_roles' => $roles,
                ],
                403
            );
        }

        return $next($request);
    }
}
