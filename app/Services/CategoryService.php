<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Str;

class CategoryService
{
    /**
     * Resolve the category ID from the request data.
     *
     * @throws \Exception
     */
    public function resolveCategoryId(int $categoryId, ?array $categoryData): int
    {
        // Case 1: Existing category
        if ($categoryId > 0) {
            $category = Category::findOrFail($categoryId);

            return $category->id;
        }

        // Case 2: New category
        if ($categoryId === -1) {
            if (! isset($categoryData) || ! is_array($categoryData)) {
                throw new \Exception('Category data is required when category_id is -1.');
            }

            return $this->processCategory($categoryData);
        }

        throw new \Exception('Invalid category_id provided.');
    }

    /**
     * Process the category data and return the category ID.
     *
     * @throws \Exception
     */
    protected function processCategory(array $categoryData): int
    {
        // Case 1: Existing category
        if ($categoryData['id'] > 0) {
            $category = Category::findOrFail($categoryData['id']);

            return $category->id;
        }

        // Case 2: New category
        if ($categoryData['id'] === -1) {
            $parentId = null;

            // Handle parent category
            if ($categoryData['parent_id'] > 0) {
                // Existing parent
                $parent = Category::findOrFail($categoryData['parent_id']);
                $parentId = $parent->id;
            } elseif ($categoryData['parent_id'] === -1) {
                // New parent category
                if (! isset($categoryData['parent'])) {
                    throw new \Exception('Parent category data is required for new subcategories.');
                }
                $parentId = $this->processCategory($categoryData['parent']); // Recursive call
            } else {
                throw new \Exception('Invalid parent_id provided.');
            }
            if ($newCategory = Category::where('name', ucwords(strtolower($categoryData['name'])))->first()) {
                return $newCategory->id;
            }
            $hierarchyData = (new Category)->calculateHierarchyData($parentId);

            // Create the new category
            $newCategory = Category::create([
                'name'      => ucwords(strtolower($categoryData['name'])),
                'parent_id' => $parentId,
                'slug'      => $this->generateUniqueSlug($categoryData['name']),
                'level'     => $hierarchyData['level'],
                'path'      => $hierarchyData['path'],
            ]);

            return $newCategory->id;
        }

        throw new \Exception('Invalid category data provided.');
    }

    /**
     * Generate a unique slug for a category.
     */
    protected function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (Category::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter++;
        }

        return $slug;
    }
}
