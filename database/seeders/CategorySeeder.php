<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUserId = User::first()->id;

        $sellerUsers = User::where('email', 'LIKE', 'seller%@example.com')->pluck('id')->toArray();

        if (empty($sellerUsers)) {
            $sellerUsers = [$adminUserId];
        }

        $categories = [
            [
                'name'        => 'Electronics',
                'icon'        => 'pi pi-desktop',
                'description' => 'Latest electronics and gadgets',
                'children'    => [
                    'Smartphones' => [
                        'icon'  => 'pi pi-mobile',
                        'items' => ['iPhone', 'Samsung Galaxy'],
                    ],
                    'Laptops' => [
                        'icon'  => 'pi pi-desktop',
                        'items' => ['Gaming Laptops', 'Business Laptops'],
                    ],
                    'Audio' => [
                        'icon'  => 'pi pi-volume-up',
                        'items' => ['Headphones', 'Speakers'],
                    ],
                ],
            ],
            [
                'name'        => 'Fashion & Apparel',
                'icon'        => 'pi pi-shopping-bag',
                'description' => 'Clothing and fashion accessories',
                'children'    => [
                    'Men\'s Clothing' => [
                        'icon'  => 'pi pi-user',
                        'items' => ['Shirts', 'Pants'],
                    ],
                    'Women\'s Clothing' => [
                        'icon'  => 'pi pi-heart',
                        'items' => ['Dresses', 'Tops'],
                    ],
                    'Footwear' => [
                        'icon'  => 'pi pi-step-forward',
                        'items' => ['Sneakers', 'Boots'],
                    ],
                ],
            ],
            [
                'name'        => 'Home & Garden',
                'icon'        => 'pi pi-home',
                'description' => 'Home improvement and garden supplies',
                'children'    => [
                    'Furniture' => [
                        'icon'  => 'pi pi-table',
                        'items' => ['Living Room', 'Bedroom'],
                    ],
                    'Appliances' => [
                        'icon'  => 'pi pi-cog',
                        'items' => ['Kitchen', 'Laundry'],
                    ],
                    'Garden' => [
                        'icon'  => 'pi pi-sun',
                        'items' => ['Plants', 'Tools'],
                    ],
                ],
            ],
            [
                'name'        => 'Sports & Outdoors',
                'icon'        => 'pi pi-bolt',
                'description' => 'Sports equipment and outdoor gear',
                'children'    => [
                    'Fitness' => [
                        'icon'  => 'pi pi-heart-fill',
                        'items' => ['Gym Equipment', 'Yoga'],
                    ],
                    'Outdoor Recreation' => [
                        'icon'  => 'pi pi-map',
                        'items' => ['Camping', 'Hiking'],
                    ],
                ],
            ],
            [
                'name'        => 'Automotive',
                'icon'        => 'pi pi-car',
                'description' => 'Auto parts and accessories',
                'children'    => [
                    'Parts' => [
                        'icon'  => 'pi pi-wrench',
                        'items' => ['Engine', 'Brakes'],
                    ],
                    'Accessories' => [
                        'icon'  => 'pi pi-star',
                        'items' => ['Interior', 'Exterior'],
                    ],
                ],
            ],
        ];

        $categoryIndex = 0;
        foreach ($categories as $categoryData) {
            $parentCategory = Category::create([
                'name'        => $categoryData['name'],
                'slug'        => str()->slug($categoryData['name']),
                'icon'        => $categoryData['icon'],
                'description' => $categoryData['description'],
                'parent_id'   => null,
                'level'       => 0,
                'path'        => null,
                'status'      => 'active',
                'created_by'  => $adminUserId,
                'updated_by'  => $adminUserId,
            ]);

            $this->addCategoryImage($parentCategory);

            $childIndex = 0;
            foreach ($categoryData['children'] as $childName => $childData) {
                $childSellerId = $sellerUsers[($categoryIndex * 10 + $childIndex) % count($sellerUsers)];

                $childCategory = Category::create([
                    'name'        => $childName,
                    'slug'        => str()->slug($childName),
                    'icon'        => $childData['icon'],
                    'description' => "Products related to {$childName}",
                    'parent_id'   => $parentCategory->id,
                    'level'       => 1,
                    'path'        => $parentCategory->id,
                    'status'      => 'active',
                    'created_by'  => $childSellerId,
                    'updated_by'  => $childSellerId,
                ]);

                $this->addCategoryImage($childCategory);

                $grandChildIndex = 0;
                foreach ($childData['items'] as $grandChildName) {
                    $grandChildSellerId = $sellerUsers[($categoryIndex * 100 + $childIndex * 10 + $grandChildIndex) % count($sellerUsers)];

                    $grandChildCategory = Category::create([
                        'name'        => $grandChildName,
                        'slug'        => str()->slug($grandChildName),
                        'icon'        => $this->getGrandChildIcon($grandChildName),
                        'description' => "Specialized {$grandChildName} products",
                        'parent_id'   => $childCategory->id,
                        'level'       => 2,
                        'path'        => $parentCategory->id.'/'.$childCategory->id,
                        'status'      => 'active',
                        'created_by'  => $grandChildSellerId,
                        'updated_by'  => $grandChildSellerId,
                    ]);

                    $this->addCategoryImage($grandChildCategory, $childCategory->name);
                    $grandChildIndex++;
                }
                $childIndex++;
            }
            $categoryIndex++;
        }
    }

    /**
     * Add image to category using local placeholder images
     */
    private function addCategoryImage(Category $category, ?string $fallbackCategoryName = null): void
    {
        $categoryName = $category->name;
        $imagePath = $this->getLocalImagePath($categoryName);

        if ($imagePath && file_exists($imagePath)) {
            try {
                $tempPath = storage_path('app/temp_'.uniqid().'_'.basename($imagePath));
                copy($imagePath, $tempPath);

                $category->addMedia($tempPath)
                    ->toMediaCollection('images');
            } catch (Exception $e) {
                Log::warning("Failed to add image to category {$category->name}: ".$e->getMessage());
            }
        }
    }

    /**
     * Get local image path for category
     */
    private function getLocalImagePath(string $categoryName): ?string
    {
        $basePath = storage_path('app/public/placeholders/');

        $imageMap = [
            'Electronics' => 'electronics.jpg',
            'Smartphones' => 'electronics.jpg',
            'Laptops'     => 'electronics.jpg',
            'Audio'       => 'electronics.jpg',

            'Fashion & Apparel' => 'fashion.jpg',
            'Men\'s Clothing'   => 'fashion.jpg',
            'Women\'s Clothing' => 'fashion.jpg',
            'Footwear'          => 'fashion.jpg',

            'Home & Garden' => 'home.jpg',
            'Furniture'     => 'home.jpg',
            'Appliances'    => 'home.jpg',
            'Garden'        => 'home.jpg',

            'Sports & Outdoors'  => 'sports.jpg',
            'Fitness'            => 'sports.jpg',
            'Outdoor Recreation' => 'sports.jpg',

            'Automotive'  => 'automotive.jpg',
            'Parts'       => 'automotive.jpg',
            'Accessories' => 'automotive.jpg',
        ];

        $filename = $imageMap[$categoryName] ?? 'default.jpg';

        return $basePath.$filename;
    }

    /**
     * Get appropriate PrimeNG icon for grandchild categories
     */
    private function getGrandChildIcon(string $categoryName): string
    {
        $iconMap = [
            'iPhone'           => 'pi pi-mobile',
            'Samsung Galaxy'   => 'pi pi-mobile',
            'Gaming Laptops'   => 'pi pi-desktop',
            'Business Laptops' => 'pi pi-briefcase',
            'Headphones'       => 'pi pi-volume-up',
            'Speakers'         => 'pi pi-volume-up',

            'Shirts'   => 'pi pi-user',
            'Pants'    => 'pi pi-user',
            'Dresses'  => 'pi pi-heart',
            'Tops'     => 'pi pi-heart',
            'Sneakers' => 'pi pi-step-forward',
            'Boots'    => 'pi pi-step-forward',

            // Home & Garden grandchildren
            'Living Room' => 'pi pi-home',
            'Bedroom'     => 'pi pi-moon',
            'Kitchen'     => 'pi pi-shopping-cart',
            'Laundry'     => 'pi pi-refresh',
            'Plants'      => 'pi pi-sun',
            'Tools'       => 'pi pi-wrench',

            // Sports grandchildren
            'Gym Equipment' => 'pi pi-heart-fill',
            'Yoga'          => 'pi pi-heart-fill',
            'Camping'       => 'pi pi-map',
            'Hiking'        => 'pi pi-map',

            'Engine'   => 'pi pi-cog',
            'Brakes'   => 'pi pi-circle',
            'Interior' => 'pi pi-car',
            'Exterior' => 'pi pi-car',
        ];

        return $iconMap[$categoryName] ?? 'pi pi-box';
    }
}
