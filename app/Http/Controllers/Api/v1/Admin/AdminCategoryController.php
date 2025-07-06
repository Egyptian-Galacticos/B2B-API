<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Category\AdminCategoryFilterRequest;
use App\Http\Requests\Admin\Category\BulkCategoryActionRequest;
use App\Http\Requests\Admin\Category\CreateCategoryRequest;
use App\Http\Requests\Admin\Category\UpdateCategoryRequest;
use App\Http\Resources\Admin\AdminCategoryResource;
use App\Services\Admin\CategoryService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;

class AdminCategoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CategoryService $categoryService
    ) {}

    /**
     * Display all categories for admin management.
     *
     * @authenticated
     */
    public function index(AdminCategoryFilterRequest $request): JsonResponse
    {
        try {
            $categories = $this->categoryService->getAllCategoriesForAdmin($request->validated(), $request);

            return $this->apiResponse(
                AdminCategoryResource::collection($categories->items()),
                'Categories retrieved successfully',
                200,
                $this->getPaginationMeta($categories)
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to retrieve categories',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Display pending categories that need approval.
     *
     * @authenticated
     */
    public function pending(AdminCategoryFilterRequest $request): JsonResponse
    {
        try {
            $categories = $this->categoryService->getPendingCategories($request);

            return $this->apiResponse(
                AdminCategoryResource::collection($categories->items()),
                'Pending categories retrieved successfully',
                200,
                $this->getPaginationMeta($categories)
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to retrieve pending categories',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Display the specified category details for admin.
     *
     * @authenticated
     */
    public function show(string $id): JsonResponse
    {
        try {
            $category = $this->categoryService->getCategoryDetailsForAdmin((int) $id);

            return $this->apiResponse(
                new AdminCategoryResource($category),
                'Category details retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to retrieve category details',
                ['error' => $e->getMessage()],
                $e->getMessage() === 'No query results for model [App\\Models\\Category] '.$id ? 404 : 500
            );
        }
    }

    /**
     * Create a new category.
     *
     * @authenticated
     */
    public function store(CreateCategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->categoryService->createCategory(
                $request->validated(),
                $request->user()->id
            );

            return $this->apiResponse(
                new AdminCategoryResource($category),
                'Category created successfully',
                201
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to create category',
                ['error' => $e->getMessage()],
                400
            );
        }
    }

    /**
     * Update the specified category.
     *
     * @authenticated
     */
    public function update(UpdateCategoryRequest $request, string $id): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $category = $this->categoryService->updateCategory(
                (int) $id,
                $validatedData,
                $request->user()->id
            );

            return $this->apiResponse(
                new AdminCategoryResource($category),
                'Category updated successfully'
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to update category',
                ['error' => $e->getMessage()],
                $e->getMessage() === 'No query results for model [App\\Models\\Category] '.$id ? 404 : 400
            );
        }
    }

    /**
     * Remove the specified category (soft delete).
     *
     * @authenticated
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->categoryService->deleteCategory((int) $id);

            return $this->apiResponse(
                null,
                'Category deleted successfully'
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to delete category',
                ['error' => $e->getMessage()],
                $e->getMessage() === 'No query results for model [App\\Models\\Category] '.$id ? 404 : 400
            );
        }
    }

    /**
     * Get trashed categories.
     *
     * @authenticated
     */
    public function trashed(AdminCategoryFilterRequest $request): JsonResponse
    {
        try {
            $categories = $this->categoryService->getTrashedCategories($request->validated(), $request);

            return $this->apiResponse(
                AdminCategoryResource::collection($categories->items()),
                'Trashed categories retrieved successfully',
                200,
                $this->getPaginationMeta($categories)
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to retrieve trashed categories',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Restore a trashed category.
     *
     * @authenticated
     */
    public function restore(string $id): JsonResponse
    {
        try {
            $category = $this->categoryService->restoreCategory((int) $id);

            return $this->apiResponse(
                new AdminCategoryResource($category),
                'Category restored successfully'
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to restore category',
                ['error' => $e->getMessage()],
                $e->getMessage() === 'No query results for model [App\\Models\\Category] '.$id ? 404 : 400
            );
        }
    }

    /**
     * Permanently delete a category.
     *
     * @authenticated
     */
    public function forceDelete(string $id): JsonResponse
    {
        try {
            $this->categoryService->forceDeleteCategory((int) $id);

            return $this->apiResponse(
                null,
                'Category permanently deleted'
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to permanently delete category',
                ['error' => $e->getMessage()],
                $e->getMessage() === 'No query results for model [App\\Models\\Category] '.$id ? 404 : 400
            );
        }
    }

    /**
     * Handle bulk actions on categories.
     *
     * @authenticated
     */
    public function bulkAction(BulkCategoryActionRequest $request): JsonResponse
    {
        try {
            $results = $this->categoryService->bulkCategoryAction(
                $request->category_ids,
                $request->action,
                $request->user()->id,
                $request->reason
            );

            if (empty($results['failed'])) {
                $message = 'All operations completed successfully';
            } elseif (empty($results['successful'])) {
                $message = 'All operations failed';
            } else {
                $message = 'Bulk operation completed with some failures';
            }

            return $this->apiResponse(
                $results,
                $message
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
     * Get category hierarchy tree for admin.
     *
     * @authenticated
     */
    public function hierarchy(): JsonResponse
    {
        try {
            $hierarchy = $this->categoryService->getCategoryHierarchy();

            return $this->apiResponse(
                $hierarchy,
                'Category hierarchy retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                'Failed to retrieve category hierarchy',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}
