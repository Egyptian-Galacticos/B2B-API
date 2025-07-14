<?php

namespace App\Services;

use App\Models\Category;
use Exception;
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
        if ($categoryId > 0) {
            $category = Category::findOrFail($categoryId);

            return $category->id;
        }

        if ($categoryId === -1) {
            if (! isset($categoryData) || ! is_array($categoryData)) {
                throw new Exception('Category data is required when category_id is -1.');
            }

            return $this->processCategory($categoryData);
        }

        throw new Exception('Invalid category_id provided.');
    }

    /**
     * Process the category data and return the category ID.
     *
     * @throws \Exception
     */
    protected function processCategory(array $categoryData): int
    {
        if ($categoryData['id'] > 0) {
            $category = Category::findOrFail($categoryData['id']);

            return $category->id;
        }

        if ($categoryData['id'] === -1) {
            $parentId = null;

            if ($categoryData['parent_id'] > 0) {
                $parent = Category::findOrFail($categoryData['parent_id']);
                $parentId = $parent->id;
            } elseif ($categoryData['parent_id'] === -1) {
                if (! isset($categoryData['parent'])) {
                    throw new Exception('Parent category data is required for new subcategories.');
                }
                $parentId = $this->processCategory($categoryData['parent']); // Recursive call
            } else {
                throw new Exception('Invalid parent_id provided.');
            }
            if ($newCategory = Category::where('name', ucwords(strtolower($categoryData['name'])))->first()) {
                $parent = $newCategory->parent;

                if ($parent->isRoot()) {
                    $path = $parent->name.' (Root Category)';
                } else {
                    $path = $parent->parent->name.' > '.$parent->name;
                }

                throw new Exception("A category with the name $newCategory->name already exists under: $path.");

            }
            $hierarchyData = (new Category)->calculateHierarchyData($parentId);

            $newCategory = Category::create([
                'name'      => ucwords(strtolower($categoryData['name'])),
                'parent_id' => $parentId,
                'slug'      => $this->generateUniqueSlug($categoryData['name']),
                'level'     => $hierarchyData['level'],
                'path'      => $hierarchyData['path'],
            ]);

            return $newCategory->id;
        }

        throw new Exception('Invalid category data provided.');
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
