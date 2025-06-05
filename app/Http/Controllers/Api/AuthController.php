<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\Company;
use App\Models\RefreshToken;
use App\Models\User;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
        $refreshToken = RefreshToken::create([
            'user_id' => $user->id,
        ]);

        $user->load('roles', 'company');

        event(new Login('api', $user, false));

        return $this->apiResponse([
            'user'          => new UserResource($user),
            'access_token'  => $token,
            'refresh_token' => $refreshToken->token,
            'expires_in'    => config('jwt.ttl') * 60,
        ], 'Login successful', 200);
    }

    /**
     * User registration
     *
     * Register a new user account and receive a JWT token.
     *
     *
     * @unauthenticated
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            // Create user with all required fields
            $user = User::create([
                'first_name'        => $validated['user']['first_name'],
                'last_name'         => $validated['user']['last_name'],
                'email'             => $validated['user']['email'],
                'password'          => Hash::make($validated['user']['password']),
                'phone_number'      => $validated['user']['phone_number'] ?? null,
                'is_email_verified' => false,
                'status'            => 'active',
            ]);

            // Assign roles
            $user->assignRole($validated['roles']);

            // Update status for sellers
            if ($user->hasRole('seller')) {
                $user->update(['status' => 'pending']);
            }
            // Log the user creation

            // Create company
            Company::create([
                'user_id'                 => $user->id,
                'name'                    => $validated['company']['name'],
                'email'                   => $validated['company']['email'],
                'tax_id'                  => $validated['company']['tax_id'] ?? null,
                'company_phone'           => $validated['company']['company_phone'] ?? null,
                'commercial_registration' => $validated['company']['commercial_registration'] ?? null,
                'website'                 => $validated['company']['website'] ?? null,
                'description'             => $validated['company']['description'] ?? null,
                'logo'                    => $validated['company']['logo'] ?? null,
                'address'                 => $validated['company']['address'],
            ]);

            // // Load roles and company relationships
            $user->load('roles', 'company');

            // Generate JWT token
            $token = JWTAuth::fromUser($user);

            // Send email verification notification
            app(\App\Services\EmailVerificationService::class)->sendVerification($user);

            DB::commit();

            event(new Registered($user));

            return $this->apiResponse([
                'user'          => new UserResource($user),
                'access_token'  => $token,
                'refresh_token' => RefreshToken::create(['user_id' => $user->id])->token,
                'expires_in'    => config('jwt.ttl') * 60,
            ], 'Registration successful. Please check your email to verify your account.', 201);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Registration failed',
                'error'   => $e->getMessage(),
            ], 500);
        }
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
    public function me(): JsonResponse
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (! $user) {
                return $this->apiResponse(null, 'User not found', 404);
            }

            return $this->apiResponse(new UserResource($user), 'User retrieved successfully', 200);
        } catch (TokenExpiredException $e) {
            return $this->apiResponse(null, 'Token has expired', 401);
        } catch (TokenInvalidException $e) {
            return $this->apiResponse(null, 'Invalid token', 401);
        } catch (\Exception $e) {
            return $this->apiResponse(null, 'Unauthorized', 401);
        }
    }

    /**
     * User logout
     *
     * Invalidate the current JWT token to log out the user.
     */
    public function logout(): JsonResponse
    {
        $token = JWTAuth::getToken();
        $user = JWTAuth::user();

        if (! $token) {
            return $this->apiResponse(null, 'No token provided', 400);
        }
        if (! $user) {
            return $this->apiResponse(null, 'User not found', 404);
        }
        try {
            RefreshToken::where('user_id', $user->id)->delete();
            JWTAuth::invalidate($token);

            return $this->apiResponse(null, 'Successfully logged out', 200);
        } catch (\Exception $e) {
            return $this->apiResponse(null, 'Failed to log out', 500);
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
     *
     * @unauthenticated
     */
    public function refresh($token): JsonResponse
    {
        try {
            $refreshToken = RefreshToken::where('token', $token)->first();

            if (! $refreshToken || ! $refreshToken->active() || $refreshToken->revoked) {
                return response()->json(['error' => 'Invalid or expired refresh token'], 401);
            }
            $user = $refreshToken->user;
            if (! $user) {
                return response()->json(['error' => 'Associated user not found'], 401);
            }
            $newToken = JWTAuth::fromUser($user);

            return $this->apiResponse([
                'access_token' => $newToken,
                'expires_in'   => config('jwt.ttl') * 60,
            ], 'Token refreshed successfully', 200);
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'Token is invalid'], 401);
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token has expired'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
    }

    /**
     * Reset user password
     *
     * Reset the user's password using a reset token.
     *
     * @response  {
     *   "error": "Failed to reset password"
     * }
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
            'email'    => $request->email,
            'token'    => $request->token,
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
