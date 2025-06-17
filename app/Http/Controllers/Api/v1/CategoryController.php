<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\QueryHandler;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of categories
     *
     * This method retrieves a list of categories with optional children and recursive children.
     */
    public function index(Request $request, QueryHandler $queryHandler): JsonResponse
    {
        try {
            $query = Category::with(['parent', 'creator', 'updater'])
                ->withCount('products');

            if ($request->boolean('include_children', true)) {
                $query->with(['children', 'recursiveChildren']);
            }

            $query = $queryHandler
                ->setBaseQuery($query)
                ->setAllowedSorts(['id', 'name', 'slug', 'description', 'status', 'level', 'sort_order', 'created_at', 'updated_at', 'products_count', 'parent.name',              'creator.name',
                ])
                ->setAllowedFilters(['id', 'name', 'slug', 'description', 'status', 'level', 'parent_id', 'created_by', 'sort_order', 'created_at', 'updated_at', 'parent.name', 'parent.slug', 'creator.name', 'creator.email',
                ])
                ->apply();

            $categories = $query->get();

            return $this->apiResponse(
                new CategoryCollection($categories),
                'Categories retrieved successfully',
                200
            );
        } catch (Exception $e) {
            return $this->apiResponse(
                null,
                'Error retrieving categories: '.$e->getMessage(),
                500
            );
        }
    }

    /**
     * Store a newly created category in storage.
     *
     * This method is responsible for storing a new category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();

            if (is_null($request->parent_id)) {
                $category = new Category;
                if (! $category->userIsAdmin($user)) {
                    return $this->apiResponseErrors(
                        null,
                        'Only administrators can create root categories',
                        403
                    );
                }
            }

            $category = new Category;

            $hierarchyData = $category->calculateHierarchyData($request->parent_id);

            $category = Category::create([
                'name'         => $request->name,
                'description'  => $request->description,
                'parent_id'    => $request->parent_id,
                'level'        => $hierarchyData['level'],
                'path'         => $hierarchyData['path'],
                'status'       => $category->determineStatusByUserRole($user),
                'icon'         => $request->icon,
                'seo_metadata' => $request->seo_metadata,
                'created_by'   => $user->id,
                'updated_by'   => $user->id,
            ]);

            $category->handleFileUploads($request);

            DB::commit();

            $category->load(['parent', 'creator', 'updater']);

            return $this->apiResponse(
                new categoryResource($category),
                'Category created successfully',
                201
            );
        } catch (Exception $e) {
            DB::rollBack();

            return $this->apiResponse(
                null,
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Display the specified category.
     *
     * This method retrieves a specific category by its ID,
     */
    public function show(int $id): JsonResponse
    {
        try {
            $category = Category::findOrFail($id);
            $category->load(['parent', 'children', 'recursiveChildren', 'creator', 'updater'])
                ->loadCount('products');

            return $this->apiResponse(
                new CategoryResource($category),
                'Category retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                null,
                'Category not found',
                404
            );
        }
    }

    /**
     * Update the specified category in storage.
     *
     * This method is responsible for updating an existing category.
     */
    public function update(UpdateCategoryRequest $request, int $categoryId): JsonResponse
    {
        DB::beginTransaction();

        try {
            $category = Category::find($categoryId);

            if (! $category) {
                return $this->apiResponseErrors(
                    null,
                    'Category not found',
                    404
                );
            }

            $user = Auth::user();

            // Check if user is trying to make this a root category (parent_id is null)
            if (is_null($request->parent_id) && ! is_null($category->parent_id)) {
                if (! $category->userIsAdmin($user)) {
                    return $this->apiResponseErrors(
                        null,
                        'Only administrators can convert categories to root categories',
                        403
                    );
                }
            }

            $updateData = [
                'name'         => $request->name,
                'description'  => $request->description,
                'parent_id'    => $request->parent_id,
                'icon'         => $request->icon,
                'seo_metadata' => $request->seo_metadata,
                'updated_by'   => $user->id,
            ];

            if ($request->has('status') && $category->userIsAdmin($user)) {
                $updateData['status'] = $request->status;
            }

            if ($request->parent_id !== $category->parent_id) {
                if ($request->parent_id) {
                    $parent = Category::findOrFail($request->parent_id);

                    if ($category->wouldCreateCircularReference($parent)) {
                        return $this->apiResponse(
                            null,
                            'Cannot set parent: would create circular reference',
                            422
                        );
                    }
                }

                $hierarchyData = $category->calculateHierarchyData($request->parent_id);
                $updateData['level'] = $hierarchyData['level'];
                $updateData['path'] = $hierarchyData['path'];
            }

            $category->update($updateData);

            $category->handleFileUploads($request);
            $category->handleFileRemovals($request);

            if (isset($updateData['level']) || isset($updateData['path'])) {
                $category->updateChildrenPaths();
            }

            DB::commit();

            $category->load(['parent', 'creator', 'updater']);

            return $this->apiResponse(
                new CategoryResource($category),
                'Category updated successfully'
            );
        } catch (Exception $e) {
            DB::rollBack();

            return $this->apiResponseErrors(
                null,
                'Error updating category: '.$e->getMessage(),
                500
            );
        }
    }

    /**
     * Remove the specified category from storage.
     *
     * This method is responsible for deleting a category.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $category = Category::findOrFail($id);
            if (! $category->canBeDeleted()) {
                return $this->apiResponse(
                    null,
                    'Cannot delete category with child categories or associated products',
                    422
                );
            }

            $category->delete();

            return $this->apiResponse(
                null,
                'Category deleted successfully'
            );
        } catch (Exception $e) {
            return $this->apiResponseErrors(
                null,
                'Category not found',
                500
            );
        }
    }

    /**
     * Restore a soft-deleted category.
     *
     * This method restores a soft-deleted category by its ID.
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $category = Category::withTrashed()->findOrFail($id);
            $category->restore();

            $category->load(['parent', 'creator', 'updater']);

            return $this->apiResponse(
                new CategoryResource($category),
                'Category restored successfully'
            );
        } catch (Exception $e) {
            return $this->apiResponse(
                null,
                'Category not found or already restored',
                500
            );
        }
    }

    /**
     * Permanently delete a soft-deleted category.
     *
     * This method force deletes a category by its ID.
     */
    public function forceDelete(int $id): JsonResponse
    {
        try {
            $category = Category::withTrashed()->findOrFail($id);
            $category->forceDelete();

            return $this->apiResponse(
                null,
                'Category permanently deleted'
            );
        } catch (Exception $e) {
            return $this->apiResponse(
                null,
                'category not found or already permanently deleted',
                500
            );
        }
    }

    /**
     * Get trashed categories.
     *
     * This method retrieves all soft-deleted categories.
     */
    public function trashed(): JsonResponse
    {
        try {
            $categories = Category::onlyTrashed()
                ->with(['parent', 'creator', 'updater'])
                ->withCount('products')
                ->get();

            if ($categories->isEmpty()) {
                return $this->apiResponse(
                    null,
                    'No trashed categories found',
                    404
                );
            }

            return $this->apiResponse(
                CategoryResource::collection($categories),
                'Trashed categories retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->apiResponse(
                null,
                'Error retrieving trashed categories',
                500
            );
        }
    }
}
