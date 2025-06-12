<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'name'        => ucwords($name),
            'description' => $this->faker->sentence(10),
            'slug'        => Str::slug($name).'-'.$this->faker->unique()->numberBetween(1, 9999),
            'parent_id'   => null,
            'path'        => null,
            'level'       => 0,
            'status'      => $this->faker->randomElement(['active', 'pending', 'inactive']),
            'icon'        => $this->faker->optional(0.3)->randomElement([
                'pi pi-desktop',
                'pi pi-shopping-bag',
                'pi pi-home',
                'pi pi-car',
                'pi pi-book',
                'pi pi-gamepad',
                'pi pi-utensils',
                'pi pi-heart',
                'pi pi-dumbbell',
                'pi pi-palette',
            ]),
            'seo_metadata' => $this->faker->optional(0.4)->passthrough([
                'title'          => $this->faker->sentence(4),
                'description'    => $this->faker->sentence(8),
                'keywords'       => implode(', ', $this->faker->words(5)),
                'og_title'       => $this->faker->sentence(3),
                'og_description' => $this->faker->sentence(6),
            ]),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    /**
     * Indicate that the category is active
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the category is pending
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the category is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the category was created by an admin
     */
    public function createdByAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'     => 'active',
            'created_by' => User::factory()->admin(),
        ]);
    }

    /**
     * Indicate that the category was created by a seller
     */
    public function createdBySeller(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'     => 'pending',
            'created_by' => User::factory()->seller(),
        ]);
    }

    /**
     * Indicate that the category is a subcategory
     */
    public function subcategory(?Category $parent = null): static
    {
        return $this->state(function (array $attributes) use ($parent) {
            $parentCategory = $parent ?? Category::factory()->create();
            $level = $parentCategory->level + 1;
            $path = $parentCategory->path ?
                $parentCategory->path.'/'.$parentCategory->id :
                (string) $parentCategory->id;

            return [
                'parent_id' => $parentCategory->id,
                'level'     => $level,
                'path'      => $path,
            ];
        });
    }

    /**
     * Create a category with children
     */
    public function withChildren(int $count = 3): static
    {
        return $this->afterCreating(function (Category $category) use ($count) {
            Category::factory()
                ->count($count)
                ->subcategory($category)
                ->create();
        });
    }

    /**
     * Create a deep nested category structure
     */
    public function deepNested(int $depth = 3): static
    {
        return $this->afterCreating(function (Category $category) use ($depth) {
            $currentParent = $category;

            for ($i = 1; $i < $depth; $i++) {
                $currentParent = Category::factory()
                    ->subcategory($currentParent)
                    ->create();
            }
        });
    }

    /**
     * Category with complete SEO metadata
     */
    public function withSeo(): static
    {
        return $this->state(function (array $attributes) {
            $name = $attributes['name'] ?? $this->faker->words(2, true);

            return [
                'seo_metadata' => [
                    'title'       => $name.' - Best Products Online',
                    'description' => "Discover amazing {$name} products with fast shipping and great prices. Shop now!",
                    'keywords'    => implode(', ', array_merge(
                        explode(' ', strtolower($name)),
                        $this->faker->words(3)
                    )),
                    'og_title'       => $name.' Collection',
                    'og_description' => "Browse our extensive {$name} collection",
                    'og_image'       => $this->faker->imageUrl(1200, 630, 'business'),
                    'twitter_card'   => 'summary_large_image',
                    'canonical_url'  => $this->faker->url(),
                    'robots'         => 'index,follow',
                    'schema_markup'  => [
                        '@context'    => 'https://schema.org',
                        '@type'       => 'ProductCategory',
                        'name'        => $name,
                        'description' => $attributes['description'] ?? $this->faker->sentence(),
                    ],
                ],
            ];
        });
    }

    /**
     * Category with icon
     */
    public function withIcon(): static
    {
        return $this->state(fn (array $attributes) => [
            'icon' => $this->faker->randomElement([
                'pi pi-desktop',
                'pi pi-mobile',
                'pi pi-shopping-bag',
                'pi pi-shoe-prints',
                'pi pi-home',
                'pi pi-building',
                'pi pi-car',
                'pi pi-bicycle',
                'pi pi-book',
                'pi pi-graduation-cap',
                'pi pi-gamepad',
                'pi pi-headphones',
                'pi pi-utensils',
                'pi pi-wine-glass',
                'pi pi-heart',
                'pi pi-pills',
                'pi pi-dumbbell',
                'pi pi-run',
                'pi pi-palette',
                'pi pi-brush',
                'pi pi-hammer',
                'pi pi-wrench',
                'pi pi-leaf',
                'pi pi-tree',
                'pi pi-baby',
                'pi pi-teddy-bear',
                'pi pi-gem',
                'pi pi-gift',
                'pi pi-camera',
                'pi pi-music',
            ]),
        ]);
    }

    /**
     * Popular category names for e-commerce
     */
    public function ecommerce(): static
    {
        $categoryData = [
            'Electronics & Technology' => 'pi pi-desktop',
            'Fashion & Apparel'        => 'pi pi-shopping-bag',
            'Home & Garden'            => 'pi pi-home',
            'Sports & Outdoors'        => 'pi pi-dumbbell',
            'Books & Media'            => 'pi pi-book',
            'Health & Beauty'          => 'pi pi-heart',
            'Toys & Games'             => 'pi pi-gift',
            'Automotive & Transport'   => 'pi pi-car',
            'Food & Beverages'         => 'pi pi-apple',
            'Jewelry & Accessories'    => 'pi pi-crown',
            'Tools & Hardware'         => 'pi pi-wrench',
            'Office & Business'        => 'pi pi-briefcase',
            'Pet Supplies'             => 'pi pi-heart',
            'Baby & Kids'              => 'pi pi-baby',
            'Art & Crafts'             => 'pi pi-palette',
            'Music & Instruments'      => 'pi pi-music',
            'Photography & Video'      => 'pi pi-camera',
            'Travel & Luggage'         => 'pi pi-map',
            'Furniture & Decor'        => 'pi pi-building',
            'Kitchen & Appliances'     => 'pi pi-utensils',
        ];

        $categoryName = $this->faker->randomElement(array_keys($categoryData));

        return $this->state(fn (array $attributes) => [
            'name'        => $categoryName,
            'icon'        => $categoryData[$categoryName],
            'description' => "Professional {$categoryName} products for businesses and consumers",
        ]);
    }

    /**
     * Create categories with realistic hierarchy
     */
    public function realistic(): static
    {
        return $this->state(function (array $attributes) {
            $categories = [
                'Electronics & Technology' => [
                    'icon'     => 'pi pi-desktop',
                    'children' => ['Smartphones & Tablets', 'Computers & Laptops', 'Audio & Video', 'Components & Parts'],
                ],
                'Fashion & Apparel' => [
                    'icon'     => 'pi pi-shopping-bag',
                    'children' => ['Professional Wear', 'Casual Clothing', 'Footwear', 'Accessories & Jewelry'],
                ],
                'Home & Garden' => [
                    'icon'     => 'pi pi-home',
                    'children' => ['Furniture & Decor', 'Kitchen & Dining', 'Garden & Outdoor', 'Storage & Organization'],
                ],
                'Business & Office' => [
                    'icon'     => 'pi pi-briefcase',
                    'children' => ['Office Supplies', 'Business Equipment', 'Furniture', 'Technology Solutions'],
                ],
                'Industrial & Manufacturing' => [
                    'icon'     => 'pi pi-cog',
                    'children' => ['Machinery & Equipment', 'Raw Materials', 'Safety Equipment', 'Tools & Hardware'],
                ],
            ];

            $category = $this->faker->randomElement(array_keys($categories));
            $data = $categories[$category];

            return [
                'name'        => $category,
                'icon'        => $data['icon'],
                'description' => "High-quality {$category} products for all your needs",
            ];
        });
    }
}
