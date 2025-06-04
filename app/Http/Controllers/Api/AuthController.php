<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\VerifyEmail;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * User login
     *
     * Authenticate user with email and password to receive a JWT token.
     *
     * @bodyParam  email anas@anas.com
     * @bodyParam string $password User's password.
     *
     * @unauthenticated
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! $token = JWTAuth::attempt($credentials)) {

            return $this->apiResponse(null, 'Invalid credentials', 401);
        }

        // Update last login timestamp
        $user = JWTAuth::user();
        $user->update(['last_login_at' => now()]);

        return $this->apiResponse([
            'user' => new UserResource($user),
            'access_token' => $token,
            'expires_in' => config('jwt.ttl') * 60,
        ], 'Login successful', 200);

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
        $user->assignRole($validatedData['roles']);
        $token = JWTAuth::fromUser($user);
        $user->notify(new VerifyEmail);

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
    public function verifyEmail(Request $request, $id): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['error' => 'Invalid or expired verification link'], 400);
        }
        $user = User::findOrFail($id);
        if ($user->is_email_verified) {
            return response()->json(['message' => 'Email already verified'], 200);
        }
        $user->is_email_verified = true;
        $user->save();

        return response()->json(['message' => 'Email successfully verified'], 200);
    }

    /**
     * Resend verification email
     *
     * Resend the email verification notification to the user.
     *
     * @response  {
     *   "message": "Verification email sent successfully"
     * }
     * @response  {
     *   "error": "Email already verified"
     * }
     *
     * @unauthenticated
     */
    public function resendVerificationEmail($id): JsonResponse
    {
        $user = User::findOrFail($id);
        if ($user->is_email_verified) {
            return response()->json(['message' => 'Email already verified'], 200);
        }
        $user->notify(new VerifyEmail);

        return response()->json(['message' => "Verification email sent successfully to {$user->email}"], 200);
    }

    /**
     * Send password reset link
     *
     * Send a password reset link to the user's email.
     *
     * @response  {
     *   "message": "Reset link sent to your email"
     * }
     * @response  {
     *   "error": "Failed to send reset link"
     * }
     *
     * @unauthenticated
     */
    public function sendResetLink(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse([
                'errors' => $validator->errors(),
            ], 'Validation failed', 422);
        }
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return $this->apiResponse(null, 'Reset link sent to your email', 200);
        }

        return $this->apiResponse(null, 'Failed to send reset link', 500);

    }

    /**
     * Reset user password
     *
     * Reset the user's password using the provided token.
     *
     * @response  {
     *   "message": "Password has been successfully reset"
     * }
     * @response  {
     *   "error": "Failed to reset password"
     * }
     *
     * @unauthenticated
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {

        $status = Password::reset([
            'email' => $request->email,
            'token' => $request->token,
            'password' => $request->password,
        ], function ($user, $password) {
            $user->password = Hash::make($password);
            $user->save();
        });
        echo $status;
        if ($status === Password::PASSWORD_RESET) {
            return $this->apiResponse(null, 'Password has been successfully reset', 200);
        }

        return $this->apiResponse(null, 'Failed to reset password', 500);
    }
}
