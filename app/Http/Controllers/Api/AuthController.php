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
use App\Services\EmailVerificationService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * @unauthenticated
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        if (! $token = JWTAuth::attempt($credentials)) {
            return $this->apiResponseErrors('Invalid credentials', ['error' => 'Unauthorized'], 401);
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
            app(EmailVerificationService::class)->sendVerification($user);

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

            return $this->apiResponseErrors('Registration failed', ['error' => $e->getMessage()], 500);
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
            return $this->apiResponseErrors('Token has expired', ['token_error' => $e->getMessage()], 401);
        } catch (TokenInvalidException $e) {
            return $this->apiResponseErrors('Invalid token', ['token_error' => $e->getMessage()], 401);
        } catch (\Exception $e) {
            return $this->apiResponseErrors('Unauthorized', ['error' => $e->getMessage()], 401);
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
            return $this->apiResponseErrors('No token provided', [], 400);
        }
        if (! $user) {
            return $this->apiResponseErrors('User not found', [], 404);
        }
        try {
            RefreshToken::where('user_id', $user->id)
                ->delete();
            JWTAuth::invalidate($token);

            return $this->apiResponse(null, 'Successfully logged out', 200);
        } catch (\Exception $e) {
            return $this->apiResponseErrors('Failed to log out', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Refresh JWT token
     *
     * Get a new JWT token using the current token.
     *
     * @unauthenticated
     */
    public function refresh($token): JsonResponse
    {
        try {
            $refreshToken = RefreshToken::where('token', $token)->first();

            // Check if refresh token exists, is active, and not revoked
            if (! $refreshToken || ! $refreshToken->isActive() || $refreshToken->revoked) {
                return $this->apiResponseErrors('Invalid or expired refresh token', [
                    'token_error' => 'Refresh token is invalid, expired, or revoked',
                ], 401);
            }

            $user = $refreshToken->user;
            if (! $user) {
                return $this->apiResponseErrors('Associated user not found', [
                    'user_error' => 'User associated with this token no longer exists',
                ], 401);
            }

            // Generate new access token
            $newToken = JWTAuth::fromUser($user);

            return $this->apiResponse([
                'access_token' => $newToken,
                'expires_in'   => config('jwt.ttl') * 60,
            ], 'Token refreshed successfully', 200);

        } catch (TokenInvalidException $e) {
            return $this->apiResponseErrors('Token is invalid', [
                'token_error' => $e->getMessage(),
            ], 401);
        } catch (TokenExpiredException $e) {
            return $this->apiResponseErrors('Token has expired', [
                'token_error' => $e->getMessage(),
            ], 401);
        } catch (\Exception $e) {
            return $this->apiResponseErrors('Token refresh failed', [
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset user password
     *
     * Reset the user's password using a reset token.
     *
     * @unauthenticated
     */
    public function sendResetLink(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->apiResponseErrors('Validation failed', $validator->errors()->toArray(), 422);
        }

        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return $this->apiResponse(null, 'Reset link sent to your email', 200);
            }

            // Handle different password reset statuses
            $message = match ($status) {
                Password::INVALID_USER    => 'User not found with that email address',
                Password::RESET_THROTTLED => 'Please wait before requesting another reset link',
                default                   => 'Failed to send reset link'
            };

            return $this->apiResponseErrors($message, ['status' => $status], 400);

        } catch (\Exception $e) {
            return $this->apiResponseErrors('Failed to send reset link', ['error' => $e->getMessage()], 500);
        }
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
        $validated = $request->validated();

        try {
            $status = Password::reset([
                'email'    => $validated['email'],
                'token'    => $validated['token'],
                'password' => $validated['password'],
            ], function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            });

            if ($status === Password::PASSWORD_RESET) {
                return $this->apiResponse(null, 'Password has been successfully reset', 200);
            }

            // Handle different password reset statuses
            $message = match ($status) {
                Password::INVALID_TOKEN => 'Invalid reset token',
                Password::INVALID_USER  => 'User not found',
                default                 => 'Failed to reset password'
            };

            return $this->apiResponseErrors($message, ['status' => $status], 400);

        } catch (\Exception $e) {
            return $this->apiResponseErrors('Password reset failed', ['error' => $e->getMessage()], 500);
        }
    }
}
