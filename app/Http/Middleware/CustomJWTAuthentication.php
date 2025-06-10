<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomJWTAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Attempt to authenticate the user using JWT
        try {
            $user = auth()->user();

            // If no authenticated user, let auth middleware handle it
            if (! $user) {
                return $next($request);
            }

        } catch (\Exception $e) {
            // Re-throw the exception so bootstrap handlers can catch it
            throw $e;
        }

        return $next($request);
    }
}
