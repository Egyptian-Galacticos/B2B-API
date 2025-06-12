<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileCompanyRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     *  remove the specified user from storage.
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): \Illuminate\Http\JsonResponse
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

            if ($user->company) {
                return $this->apiResponseErrors(
                    'Deletion not allowed',
                    ['Users associated with a company cannot be deleted.'],
                    403
                );
            }

            $user->delete();

            return $this->apiResponse(null, 'User account deleted successfully.', 200);
        } catch (\Exception $e) {
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
    public function updateProfile(UpdateProfileRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = Auth::user();

            // Get validated data and remove file fields as they're handled separately
            $validated = $request->validated();
            $user->update($validated);
            $userData = new UserResource($user->fresh());

            return $this->apiResponse($userData, 'Profile updated successfully.', 200);

        } catch (\Exception $e) {
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

            // Verify current password
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

            // Update password
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            return $this->apiResponse(null, 'Password updated successfully.', 200);

        } catch (\Exception $e) {
            return $this->apiResponseErrors(
                'Server error',
                ['An unexpected error occurred while updating the password.', $e->getMessage()],
                500
            );
        }
    }

    /**
     * Update company information
     */
    public function updateCompany(UpdateProfileCompanyRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = Auth::user();

            // Check if user has a company
            if (! $user->company) {
                return $this->apiResponseErrors(
                    'Company not found',
                    ['No company found for this user.'],
                    404
                );
            }

            // Get validated data
            $updateData = $request->validated();

            // If email is being updated, reset email verification
            if ($request->has('email') && $request->email !== $user->company->email) {
                $updateData['is_email_verified'] = false;
            }

            $user->company->update($updateData);

            $companyData = new CompanyResource($user->company->fresh());

            return $this->apiResponse($companyData, 'Company information updated successfully.', 200);

        } catch (\Exception $e) {
            return $this->apiResponseErrors(
                'Server error',
                ['An unexpected error occurred while updating the company information.', $e->getMessage()],
                500
            );
        }
    }

    public function restore($id): \Illuminate\Http\JsonResponse
    {
        try {
            if (! auth()->user()->isAdmin()) {
                return $this->apiResponseErrors(
                    'Access denied',
                    ['Only administrators can restore user accounts.'],
                    403
                );
            }

            $user = User::withTrashed()->find($id);

            if (! $user) {
                return $this->apiResponseErrors(
                    'User not found',
                    ['The requested user account could not be found.'],
                    404
                );
            }

            if (! $user->trashed()) {
                return $this->apiResponseErrors(
                    'Action not required',
                    ['This user account is already active.'],
                    400
                );
            }

            $user->restore();

            return $this->apiResponse(null, 'User account restored successfully.', 200);
        } catch (\Exception $e) {
            return $this->apiResponseErrors(
                'Server error',
                ['An unexpected error occurred while restoring the user account.', $e->getMessage()],
                500
            );
        }
    }

    public function forceDelete($id): \Illuminate\Http\JsonResponse
    {
        try {
            if (! auth()->user()->isAdmin()) {
                return $this->apiResponseErrors(
                    'Access denied',
                    ['Only administrators can permanently delete user accounts.'],
                    403
                );
            }

            $user = User::withTrashed()->find($id);

            if (! $user) {
                return $this->apiResponseErrors(
                    'User not found',
                    ['The requested user account could not be found.'],
                    404
                );
            }

            if (! $user->trashed()) {
                return $this->apiResponseErrors(
                    'Deletion not allowed',
                    ['Only soft-deleted user accounts can be permanently removed.'],
                    400
                );
            }

            $user->forceDelete();

            return $this->apiResponse(null, 'User account permanently deleted.', 200);
        } catch (\Exception $e) {
            return $this->apiResponseErrors(
                'Server error',
                ['An unexpected error occurred while permanently deleting the user account.', $e->getMessage()],
                500
            );
        }
    }
}
