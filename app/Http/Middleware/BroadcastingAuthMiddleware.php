<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class BroadcastingAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Get the token from multiple sources
            $token = $request->bearerToken()
                  ?: $request->header('Authorization')
                  ?: $request->input('token')
                  ?: $request->input('auth.token')
                  ?: $request->input('auth')['token'] ?? null;

            // Remove "Bearer " prefix if present
            if ($token && str_starts_with($token, 'Bearer ')) {
                $token = substr($token, 7);
            }

            if (! $token) {
                return response()->json([
                    'error'   => 'Token not provided',
                    'message' => 'No authentication token found in request',
                ], 401);
            }

            // Authenticate the user using JWT
            $user = JWTAuth::setToken($token)->authenticate();

            if (! $user) {
                return response()->json([
                    'error'   => 'Invalid token',
                    'message' => 'Token is valid format but user not found',
                ], 401);
            }

            // Set the authenticated user
            Auth::setUser($user);

            return $next($request);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'error'   => 'Token expired',
                'message' => 'Your authentication token has expired',
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'error'   => 'Invalid token format',
                'message' => 'The token format is invalid',
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'error'   => 'JWT error',
                'message' => 'JWT processing error: '.$e->getMessage(),
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Authentication failed',
                'message' => 'General authentication error: '.$e->getMessage(),
            ], 401);
        }
    }
}
