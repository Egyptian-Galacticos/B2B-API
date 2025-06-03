<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * User login
     *
     * Authenticate user with email and password to receive a JWT token.
     *
     *
     * @unauthenticated
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! $token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'error' => 'Invalid credentials',
                'message' => 'The provided email or password is incorrect.',
            ], 401);
        }

        // Update last login timestamp
        $user = JWTAuth::user();
        $user->update(['last_login_at' => now()]);

        return response()->json([
            'message' => 'Login successful',
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ]);
    }

    /**
     * User registration
     *
     * Register a new user account and receive a JWT token.
     *
     * @response array{
     * message: string,
     * user: User,
     * access_token: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.....',
     * token_type: 'bearer',
     * expires_in: int
     * }
     *
     * @unauthenticated
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        $user = User::create([
            'first_name' => $validatedData['first_name'],
            'last_name' => $validatedData['last_name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'phone_number' => $validatedData['phone_number'] ?? null,
            'profile_image_url' => $validatedData['profile_image_url'] ?? null,
            'is_email_verified' => false, // Will be verified later via email
            'status' => 'active',
            'last_login_at' => now(),
        ]);
        // Assign role if provided
        $user->assignRole($validatedData['role']);
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Registration successful',
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ], 201);
    }

    /**
     * Get authenticated user
     *
     * Retrieve the currently authenticated user's information.
     *
     * @response User
     *
     * @headers Authorization Bearer {token}
     */
    public function me()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            return new UserResource($user);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
    }

    /**
     * User logout
     *
     * Invalidate the current JWT token to log out the user.
     *
     *
     * @response  {
     *   "message": "Successfully logged out"
     * }
     * @response  {
     *   "error": "Token not provided"
     * }
     * @response  {
     *   "error": "Failed to logout"
     * }
     */
    public function logout(): JsonResponse
    {
        $token = JWTAuth::getToken();

        if (! $token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        try {
            JWTAuth::invalidate($token);

            return response()->json(['message' => 'Successfully logged out']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to logout'], 500);
        }
    }

    /**
     * Refresh JWT token
     *
     * Get a new JWT token using the current token.
     *
     *
     * @response  {
     *   "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
     *   "token_type": "bearer",
     *   "expires_in": 3600
     * }
     * @response  {
     *   "error": "Failed to refresh token"
     * }
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());

            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ]);
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'Token is invalid'], 401);
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token has expired'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token refresh failed'], 401);
        }
    }

    /**
     * Reset user password
     *
     * Reset the user's password using a reset token.
     *
     * @response  {
     *   "message": "Password has been successfully reset",
     *   "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
     *   "token_type": "bearer",
     *   "expires_in": 3600,
     *   "user": User
     * }
     * @response  {
     *   "error": "Failed to reset password"
     * }
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        // Validate the request
        $validatedData = $request->validated();

        // Use Laravel's Password facade to handle the reset
        $status = Password::reset(
            [
                'email' => $validatedData['email'],
                'password' => $validatedData['password'],
                'password_confirmation' => $validatedData['password_confirmation'],
                'token' => $validatedData['token'],
            ],
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );
        $token = JWTAuth::attempt([
            'email' => $validatedData['email'],
            'password' => $validatedData['password'],
        ]);

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password has been successfully reset',
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ]);
        }

        return response()->json(['error' => 'Failed to reset password'], 400);
    }
}
