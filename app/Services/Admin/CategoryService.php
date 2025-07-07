<?php

namespace App\Services\Admin;

use App\Models\Category;
use App\Services\QueryHandler;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    /**
     * Get all categories with filtering and pagination for admin.
     */
    public function getAllCategoriesForAdmin(array $filters, Request $request): LengthAwarePaginator
    {
        $query = Category::with([
            'parent:id,name,slug,level',
            'children:id,name,slug,parent_id',
            'creator:id,first_name,last_name,email',
            'updater:id,first_name,last_name,email',
            'media',
        ])
            ->withCount(['products', 'children'])
            ->withTrashed();

        $queryHandler = new QueryHandler($request);
        $queryHandler->setBaseQuery($query)
            ->setAllowedSorts([
                'id',
                'name',
                'slug',
                'status',
                'level',
                'created_at',
                'updated_at',
                'products_count',
                'children_count',
                'parent.name',
            ])
            ->setAllowedFilters([
                'name',
                'slug',
                'status',
                'level',
                'parent_id',
                'created_by',
                'updated_by',
                'created_at',
                'updated_at',
            ]);

        return $queryHandler->apply()->paginate($request->get('per_page', 15));
    }

    /**
     * Get pending categories that need approval.
     */
    public function getPendingCategories(Request $request): LengthAwarePaginator
    {
        $perPage = $request->get('per_page', 15);

        return Category::with([
            'parent:id,name,slug,level',
            'children:id,name,slug,parent_id',
            'creator:id,first_name,last_name,email',
            'updater:id,first_name,last_name,email',
            'media',
        ])
            ->withCount(['products', 'children'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get category details for admin.
     */
    public function getCategoryDetailsForAdmin(int $id): Category
    {
        return Category::with([
            'parent:id,name,slug,level',
            'children:id,name,slug,parent_id,level,status',
            'products:id,name,slug,is_active,is_approved,created_at',
            'creator:id,first_name,last_name,email',
            'updater:id,first_name,last_name,email',
            'media',
        ])
            ->withCount(['products', 'children'])
            ->withTrashed()
            ->findOrFail($id);
    }

    /**
     * Create a new category.
     */
    public function createCategory(array $data, int $adminId): Category
    {
        DB::beginTransaction();

        try {
            $hierarchyData = [];
            if (isset($data['parent_id']) && $data['parent_id']) {
                $parent = Category::findOrFail($data['parent_id']);
                $hierarchyData = [
                    'level' => $parent->level + 1,
                    'path'  => $parent->path ? $parent->path.'/'.$parent->id : (string) $parent->id,
                ];

                if ($this->wouldCreateCircularReference($parent, null)) {
                    throw new Exception('Cannot create circular reference in category hierarchy');
                }
            }

            $category = Category::create([
                ...$data,
                ...$hierarchyData,
                'status'     => 'active',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);

            if (isset($data['image_file']) || isset($data['icon_file'])) {
                $this->handleFileUploads($category, $data);
            }

            DB::commit();

            return $category->load([
                'parent:id,name,slug',
                'creator:id,first_name,last_name,email',
                'media',
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update a category.
     */
    public function updateCategory(int $id, array $data, int $adminId): Category
    {
        DB::beginTransaction();

        try {
            $category = Category::withTrashed()->findOrFail($id);

            if (isset($data['parent_id']) && $data['parent_id'] !== $category->parent_id) {
                $hierarchyData = [];

                if ($data['parent_id']) {
                    $parent = Category::findOrFail($data['parent_id']);

                    if ($this->wouldCreateCircularReference($parent, $category)) {
                        throw new Exception('Cannot create circular reference in category hierarchy');
                    }

                    $hierarchyData = [
                        'level' => $parent->level + 1,
                        'path'  => $parent->path ? $parent->path.'/'.$parent->id : (string) $parent->id,
                    ];
                } else {
                    $hierarchyData = [
                        'level' => 0,
                        'path'  => null,
                    ];
                }

                $data = array_merge($data, $hierarchyData);
            }

            $category->update([
                ...$data,
                'updated_by' => $adminId,
            ]);

            $this->handleFileOperations($category, $data);

            if (isset($hierarchyData)) {
                $this->updateChildrenPaths($category);
            }

            DB::commit();

            return $category->load([
                'parent:id,name,slug,level',
                'children:id,name,slug,parent_id',
                'creator:id,first_name,last_name,email',
                'updater:id,first_name,last_name,email',
                'media',
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a category (soft delete).
     */
    public function deleteCategory(int $id): void
    {
        DB::beginTransaction();

        try {
            $category = Category::findOrFail($id);

            if (! $this->canCategoryBeDeleted($category)) {
                throw new Exception('Cannot delete category with active children or products');
            }

            $category->delete();

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Get trashed categories.
     */
    public function getTrashedCategories(array $filters, Request $request): LengthAwarePaginator
    {
        $query = Category::onlyTrashed()
            ->with([
                'parent:id,name,slug',
                'creator:id,first_name,last_name,email',
                'updater:id,first_name,last_name,email',
            ])
            ->withCount(['products', 'children']);

        $queryHandler = new QueryHandler($request);
        $queryHandler->setBaseQuery($query)
            ->setAllowedSorts([
                'id',
                'name',
                'slug',
                'level',
                'deleted_at',
                'created_at',
                'products_count',
                'children_count',
            ])
            ->setAllowedFilters([
                'name',
                'slug',
                'level',
                'parent_id',
                'created_by',
                'deleted_at',
            ]);

        return $queryHandler->apply()->paginate($request->get('per_page', 15));
    }

    /**
     * Restore a trashed category.
     */
    public function restoreCategory(int $id): Category
    {
        DB::beginTransaction();

        try {
            $category = Category::onlyTrashed()->findOrFail($id);
            $category->restore();

            DB::commit();

            return $category->load([
                'parent:id,name,slug',
                'creator:id,first_name,last_name,email',
                'updater:id,first_name,last_name,email',
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Force delete a category.
     */
    public function forceDeleteCategory(int $id): void
    {
        DB::beginTransaction();

        try {
            $category = Category::onlyTrashed()->findOrFail($id);

            if ($category->children()->exists() || $category->products()->exists()) {
                throw new Exception('Category cannot be permanently deleted - has children or products');
            }

            $category->clearMediaCollection('images');
            $category->clearMediaCollection('icons');

            $category->forceDelete();

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Perform bulk actions on categories.
     */
    public function bulkCategoryAction(array $categoryIds, string $action, int $adminId, ?string $reason = null): array
    {
        DB::beginTransaction();

        try {
            $successful = [];
            $failed = [];

            foreach ($categoryIds as $categoryId) {
                try {
                    $category = Category::withTrashed()->findOrFail($categoryId);

                    switch ($action) {
                        case 'activate':
                        case 'approve':
                            if ($category->status === 'active') {
                                throw new Exception('Category is already active');
                            }
                            $category->update(['status' => 'active', 'updated_by' => $adminId]);
                            break;
                        case 'deactivate':
                            if ($category->status === 'inactive') {
                                throw new Exception('Category is already inactive');
                            }
                            $category->update(['status' => 'inactive', 'updated_by' => $adminId]);
                            break;
                        case 'delete':
                            if ($category->trashed()) {
                                throw new Exception('Category is already deleted');
                            }
                            if ($this->canCategoryBeDeleted($category)) {
                                $category->delete();
                            } else {
                                throw new Exception('Category has active children or products');
                            }
                            break;
                        case 'restore':
                            if (! $category->trashed()) {
                                throw new Exception('Category is not deleted');
                            }
                            $category->restore();
                            break;
                        case 'force_delete':
                            if (! $category->trashed()) {
                                throw new Exception('Category must be deleted first before force delete');
                            }
                            if ($category->children()->exists() || $category->products()->exists()) {
                                throw new Exception('Category cannot be permanently deleted - has children or products');
                            }
                            $category->clearMediaCollection('images');
                            $category->clearMediaCollection('icons');
                            $category->forceDelete();
                            break;
                    }

                    $successful[] = [
                        'id'   => $categoryId,
                        'name' => $category->name,
                    ];

                } catch (Exception $e) {
                    $failed[] = [
                        'id'    => $categoryId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            return [
                'successful' => $successful,
                'failed'     => $failed,
            ];

        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Get category hierarchy tree.
     */
    public function getCategoryHierarchy(): array
    {
        $categories = Category::with('recursiveChildren')
            ->whereNull('parent_id')
            ->orderBy('id')
            ->get();

        return $this->buildHierarchyTree($categories);
    }

    /**
     * Check if setting parent would create circular reference.
     */
    private function wouldCreateCircularReference(?Category $parent, ?Category $category): bool
    {
        if (! $parent || ! $category) {
            return false;
        }

        $currentParent = $parent;
        while ($currentParent) {
            if ($currentParent->id === $category->id) {
                return true;
            }
            $currentParent = $currentParent->parent;
        }

        return false;
    }

    /**
     * Update paths for all children when parent hierarchy changes.
     */
    private function updateChildrenPaths(Category $category): void
    {
        if ($category->level >= 2) {
            return;
        }

        $children = Category::where('parent_id', $category->id)->get();

        foreach ($children as $child) {
            $newLevel = $category->level + 1;
            if ($newLevel > 2) {
                continue;
            }

            $newPath = $category->path ? $category->path.'/'.$category->id : (string) $category->id;
            $child->update([
                'level' => $newLevel,
                'path'  => $newPath,
            ]);

            if ($newLevel < 2) {
                $this->updateChildrenPaths($child);
            }
        }
    }

    /**
     * Check if category can be deleted.
     */
    private function canCategoryBeDeleted(Category $category): bool
    {
        return ! $category->children()->exists() && ! $category->products()->exists();
    }

    /**
     * Handle file uploads and removals.
     */
    private function handleFileOperations(Category $category, array $data): void
    {
        if (isset($data['remove_image']) && $data['remove_image']) {
            $category->clearMediaCollection('images');
        }

        if (isset($data['remove_icon']) && $data['remove_icon']) {
            $category->clearMediaCollection('icons');
        }

        $this->handleFileUploads($category, $data);
    }

    /**
     * Handle file uploads.
     */
    private function handleFileUploads(Category $category, array $data): void
    {
        if (isset($data['image_file'])) {
            $category->clearMediaCollection('images');
            $category->addMediaFromRequest('image_file')
                ->toMediaCollection('images');
        }

        if (isset($data['icon_file'])) {
            $category->clearMediaCollection('icons');
            $category->addMediaFromRequest('icon_file')
                ->toMediaCollection('icons');
        }
    }

    /**
     * Build hierarchy tree structure.
     */
    private function buildHierarchyTree($categories): array
    {
        return $categories->map(function ($category) {
            return [
                'id'             => $category->id,
                'name'           => $category->name,
                'slug'           => $category->slug,
                'level'          => $category->level,
                'status'         => $category->status,
                'products_count' => $category->products_count ?? 0,
                'children'       => $category->children->isNotEmpty()
                    ? $this->buildHierarchyTree($category->children)
                    : [],
            ];
        })->toArray();
    }
}
