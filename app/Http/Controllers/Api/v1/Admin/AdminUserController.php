<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserFilterRequest;
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
}
