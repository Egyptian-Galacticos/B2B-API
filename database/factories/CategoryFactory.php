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
                'fa-solid fa-laptop',
                'fa-solid fa-tshirt',
                'fa-solid fa-home',
                'fa-solid fa-car',
                'fa-solid fa-book',
                'fa-solid fa-gamepad',
                'fa-solid fa-utensils',
                'fa-solid fa-heartbeat',
                'fa-solid fa-dumbbell',
                'fa-solid fa-palette',
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
                'fa-solid fa-laptop',
                'fa-solid fa-mobile-alt',
                'fa-solid fa-tshirt',
                'fa-solid fa-shoe-prints',
                'fa-solid fa-home',
                'fa-solid fa-couch',
                'fa-solid fa-car',
                'fa-solid fa-motorcycle',
                'fa-solid fa-book',
                'fa-solid fa-graduation-cap',
                'fa-solid fa-gamepad',
                'fa-solid fa-headphones',
                'fa-solid fa-utensils',
                'fa-solid fa-wine-glass',
                'fa-solid fa-heartbeat',
                'fa-solid fa-pills',
                'fa-solid fa-dumbbell',
                'fa-solid fa-running',
                'fa-solid fa-palette',
                'fa-solid fa-brush',
                'fa-solid fa-hammer',
                'fa-solid fa-wrench',
                'fa-solid fa-seedling',
                'fa-solid fa-leaf',
                'fa-solid fa-baby',
                'fa-solid fa-teddy-bear',
                'fa-solid fa-gem',
                'fa-solid fa-ring',
                'fa-solid fa-camera',
                'fa-solid fa-music',
            ]),
        ]);
    }

    /**
     * Popular category names for e-commerce
     */
    public function ecommerce(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->randomElement([
                'Electronics',
                'Clothing & Fashion',
                'Home & Garden',
                'Sports & Outdoors',
                'Books & Media',
                'Health & Beauty',
                'Toys & Games',
                'Automotive',
                'Food & Beverages',
                'Jewelry & Accessories',
                'Tools & Hardware',
                'Office Supplies',
                'Pet Supplies',
                'Baby & Kids',
                'Art & Crafts',
                'Music & Instruments',
                'Photography',
                'Travel & Luggage',
                'Furniture',
                'Appliances',
            ]),
        ]);
    }

    /**
     * Create categories with realistic hierarchy
     */
    public function realistic(): static
    {
        return $this->state(function (array $attributes) {
            $categories = [
                'Electronics' => [
                    'icon'     => 'fa-solid fa-laptop',
                    'children' => ['Smartphones', 'Laptops', 'Headphones', 'Cameras'],
                ],
                'Clothing' => [
                    'icon'     => 'fa-solid fa-tshirt',
                    'children' => ['Men\'s Clothing', 'Women\'s Clothing', 'Shoes', 'Accessories'],
                ],
                'Home & Garden' => [
                    'icon'     => 'fa-solid fa-home',
                    'children' => ['Furniture', 'Kitchen', 'Decor', 'Garden Tools'],
                ],
                'Sports' => [
                    'icon'     => 'fa-solid fa-dumbbell',
                    'children' => ['Fitness Equipment', 'Outdoor Sports', 'Team Sports', 'Water Sports'],
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
