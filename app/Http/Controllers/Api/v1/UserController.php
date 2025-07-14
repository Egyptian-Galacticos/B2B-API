<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\EmailVerificationService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;

    /**
     *  remove the specified user from storage.
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            if (! $user) {
                return $this->apiResponseErrors(
                    'User not found',
                    ['The requested user account could not be found.'],
                    404
                );
            }
            $auth_user = auth()->user();
            if ($user->id !== $auth_user->id && ! $auth_user->isAdmin()) {
                return $this->apiResponseErrors(
                    'Access denied',
                    ['You are not authorized to delete this user account.'],
                    403
                );
            }

            $user->delete();
            $user->company->delete();

            return $this->apiResponse(null, 'User account deleted successfully.', 200);
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Server error',
                ['An unexpected error occurred while deleting the user account.', $e->getMessage()],
                500
            );
        }
    }

    /**
     * Update user profile
     *
     * @authenticated
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $validated = $request->validated();
            $user->update($validated);
            $userData = new UserResource($user->fresh());
            if ($user->wasChanged('email')) {
                app(EmailVerificationService::class)->sendVerification($user);
            }

            return $this->apiResponse($userData, 'Profile updated successfully.', 200);

        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Server error',
                ['An unexpected error occurred while updating the profile.', $e->getMessage()],
                500
            );
        }
    }

    /**
     * Update user password
     *
     * @authenticated
     */
    public function updatePassword(UpdatePasswordRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = Auth::user();

            if (! Hash::check($request->current_password, $user->password)) {
                return $this->apiResponseErrors(
                    'Current password is incorrect',
                    ['The provided password does not match your current password.'],
                    422
                );
            }
            if (Hash::check($request->new_password, $user->password)) {
                return $this->apiResponseErrors(
                    'new paassword cannot be same as current password',
                    ['The provided password cannot be the same as your current password.'],
                    422
                );
            }

            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            return $this->apiResponse(null, 'Password updated successfully.', 200);

        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Server error',
                ['An unexpected error occurred while updating the password.', $e->getMessage()],
                500
            );
        }
    }
}
