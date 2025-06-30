<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\BulkUserActionRequest;
use App\Http\Requests\Admin\User\ReviewSellerRegistrationRequest;
use App\Http\Requests\Admin\User\UpdateUserRequest;
use App\Http\Requests\Admin\User\UserFilterRequest;
use App\Http\Resources\Admin\AdminUserDetailResource;
use App\Http\Resources\Admin\SellerRegistrationReviewResource;
use App\Http\Resources\UserResource;
use App\Services\Admin\UserService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Get All Users (Admin)
     *
     * Retrieve a paginated list of all users with filtering capabilities.
     * Only accessible by admin users.
     */
    public function index(UserFilterRequest $request): JsonResponse
    {
        try {
            $currentAdminId = $request->user()->id;

            $users = $this->userService->getAllUsersWithFilters($request, $currentAdminId);

            return $this->apiResponse(
                UserResource::collection($users->items()),
                'Users retrieved successfully',
                200,
                $this->getPaginationMeta($users)
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to retrieve users',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Update User Status (Admin)
     *
     * Update user status to suspend/activate accounts.
     * Only accessible by admin users.
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        try {
            $updatedUser = $this->userService->updateUserStatus(
                $id,
                $request->validated(),
                $request->user()->id
            );

            return $this->apiResponse(
                new UserResource($updatedUser),
                'User status updated successfully'
            );

        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to update user status',
                ['error' => $e->getMessage()],
                $e->getMessage() === 'No query results for model [App\\Models\\User] 1' ? 404 : 400
            );
        }
    }

    /**
     * Get User Details (Admin)
     *
     * Retrieve detailed information about a specific user.
     * Only accessible by admin users.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userService->getUserDetails($id);

            return $this->apiResponse(
                new AdminUserDetailResource($user),
                'User details retrieved successfully'
            );

        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to retrieve user details',
                ['error' => $e->getMessage()],
                $e->getMessage() === 'No query results for model [App\\Models\\User] '.$id ? 404 : 500
            );
        }
    }

    /**
     * Bulk User Actions (Admin)
     *
     * Perform bulk operations on multiple users (suspend, activate, delete).
     * Only accessible by admin users.
     */
    public function bulkAction(BulkUserActionRequest $request): JsonResponse
    {
        try {
            $results = $this->userService->bulkUserAction(
                $request->user_ids,
                $request->action,
                $request->user()->id,
                $request->reason
            );

            return $this->apiResponse(
                $results['successful'],
                'Bulk operation completed successfully',
                200
            );

        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to perform bulk operation',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get Pending Seller Registrations (Admin)
     *
     * Retrieve companies awaiting verification to become sellers.
     * Only accessible by admin users.
     */
    public function pendingSellerRegistrations(): JsonResponse
    {
        try {
            $pendingRegistrations = $this->userService->getPendingSellerRegistrations();

            return $this->apiResponse(
                UserResource::collection($pendingRegistrations),
                'Pending seller registrations retrieved successfully'
            );

        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to retrieve pending seller registrations',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Review Seller Registration (Admin)
     *
     * Approve or reject a seller registration application.
     * Only accessible by admin users.
     */
    public function reviewSellerRegistration(ReviewSellerRegistrationRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->userService->reviewSellerRegistration(
                $id,
                $request->action,
                $request->reason,
                $request->notes,
                $request->user()->id
            );

            return $this->apiResponse(
                new SellerRegistrationReviewResource($result),
                'Seller registration reviewed successfully'
            );

        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to review seller registration',
                ['error' => $e->getMessage()],
                $e->getMessage() === 'No query results for model [App\\Models\\Company] '.$id ? 404 : 400
            );
        }
    }
}
