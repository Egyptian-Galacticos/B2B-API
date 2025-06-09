<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Realistic product categories and brands
        $categories = [
            'Electronics'   => ['Apple', 'Samsung', 'Sony', 'LG', 'Huawei'],
            'Fashion'       => ['Nike', 'Adidas', 'Zara', 'H&M', 'Gucci'],
            'Home & Garden' => ['IKEA', 'Phillips', 'Black+Decker', 'DeWalt', 'Bosch'],
            'Industrial'    => ['Caterpillar', 'John Deere', 'Siemens', '3M', 'GE'],
            'Automotive'    => ['Toyota', 'BMW', 'Mercedes', 'Ford', 'Honda'],
        ];

        $categoryName = $this->faker->randomElement(array_keys($categories));
        $brand = $this->faker->randomElement($categories[$categoryName]);

        // Product types based on category
        $productTypes = [
            'Electronics'   => ['Smartphone', 'Laptop', 'Tablet', 'Camera', 'Headphones', 'Smart Watch'],
            'Fashion'       => ['T-Shirt', 'Jeans', 'Sneakers', 'Jacket', 'Dress', 'Handbag'],
            'Home & Garden' => ['Chair', 'Table', 'Drill', 'Vacuum Cleaner', 'Lamp'],
            'Industrial'    => ['Generator', 'Compressor', 'Welding Machine', 'Safety Equipment'],
            'Automotive'    => ['Engine Parts', 'Tires', 'Battery', 'Brake Pads', 'Oil Filter'],
        ];

        $productType = $this->faker->randomElement($productTypes[$categoryName]);
        $modelNumber = $this->faker->bothify('??-####');

        return [
            'brand'        => $brand,
            'model_number' => $modelNumber,
            'seller_id'    => User::factory(),
            'sku'          => strtoupper($this->faker->bothify('##??####')),
            'name'         => $brand.' '.$productType.' '.$modelNumber,
            'description'  => $this->generateProductDescription($categoryName, $productType, $brand),
            'hs_code'      => $this->faker->numerify('####.##.##'),
            'price'        => $this->generatePrice($categoryName),
            'currency'     => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'origin'       => $this->faker->randomElement([
                'United States', 'Germany', 'China', 'Japan', 'South Korea',
                'Italy', 'France', 'United Kingdom', 'Canada', 'Australia',
            ]),
            'category_id'      => Category::factory(),
            'specifications'   => $this->generateSpecifications($categoryName, $productType),
            'dimensions'       => $this->generateDimensions($categoryName),
            'is_active'        => $this->faker->boolean(95), // 95% chance of being active
            'is_approved'      => $this->faker->boolean(85), // 85% chance of being approved
            'is_featured'      => $this->faker->boolean(15), // 15% chance of being featured
            'sample_available' => $this->faker->boolean(60), // 60% chance of sample being available
            'sample_price'     => $this->faker->randomFloat(2, 5, 200),
        ];
    }

    /**
     * Generate realistic product description
     */
    private function generateProductDescription(string $category, string $type, string $brand): string
    {
        $features = [
            'Electronics' => [
                'High-performance processor', 'Crystal clear display', 'Long-lasting battery',
                'Advanced camera system', 'Water-resistant design', 'Wireless connectivity',
            ],
            'Fashion' => [
                'Premium quality materials', 'Comfortable fit', 'Stylish design',
                'Durable construction', 'Easy care instructions', 'Versatile styling',
            ],
            'Home & Garden' => [
                'Ergonomic design', 'Energy efficient', 'Easy assembly',
                'Durable materials', 'Multiple settings', 'Safety features',
            ],
            'Industrial' => [
                'Heavy-duty construction', 'High efficiency', 'Safety certified',
                'Maintenance-free operation', 'Industrial grade', 'Long service life',
            ],
            'Automotive' => [
                'OEM quality', 'Perfect fit', 'Enhanced performance',
                'Corrosion resistant', 'Easy installation', 'Warranty included',
            ],
        ];

        $categoryFeatures = $features[$category] ?? $features['Electronics'];
        $selectedFeatures = $this->faker->randomElements($categoryFeatures, $this->faker->numberBetween(2, 4));

        return "Professional grade {$type} from {$brand}. Features include: ".
               implode(', ', $selectedFeatures).'. '.
               $this->faker->sentence(10);
    }

    /**
     * Generate realistic pricing based on category
     */
    private function generatePrice(string $category): float
    {
        $priceRanges = [
            'Electronics'   => [50, 2000],
            'Fashion'       => [20, 500],
            'Home & Garden' => [30, 800],
            'Industrial'    => [200, 5000],
            'Automotive'    => [25, 1200],
        ];

        $range = $priceRanges[$category] ?? [10, 1000];

        return $this->faker->randomFloat(2, $range[0], $range[1]);
    }

    /**
     * Generate realistic specifications based on category
     */
    private function generateSpecifications(string $category, string $type): array
    {
        $baseSpecs = [
            'weight' => $this->faker->randomFloat(2, 0.1, 50).' kg',
            'color'  => $this->faker->colorName(),
        ];

        $categorySpecs = [
            'Electronics' => [
                'battery_life' => $this->faker->numberBetween(6, 48).' hours',
                'connectivity' => $this->faker->randomElement(['WiFi', 'Bluetooth', 'USB-C', '5G']),
                'warranty'     => $this->faker->numberBetween(1, 3).' years',
            ],
            'Fashion' => [
                'material'          => $this->faker->randomElement(['Cotton', 'Polyester', 'Leather', 'Wool']),
                'size_range'        => 'XS-XXL',
                'care_instructions' => 'Machine washable',
            ],
            'Home & Garden' => [
                'power_consumption' => $this->faker->numberBetween(100, 2000).'W',
                'material'          => $this->faker->randomElement(['Steel', 'Aluminum', 'Plastic', 'Wood']),
                'certification'     => 'CE, RoHS',
            ],
            'Industrial' => [
                'operating_temperature' => '-20°C to +60°C',
                'protection_rating'     => 'IP65',
                'certification'         => 'ISO 9001, CE',
            ],
            'Automotive' => [
                'compatibility' => 'Universal fit',
                'material'      => $this->faker->randomElement(['Steel', 'Aluminum', 'Rubber', 'Plastic']),
                'warranty'      => $this->faker->numberBetween(1, 5).' years',
            ],
        ];

        return array_merge($baseSpecs, $categorySpecs[$category] ?? []);
    }

    /**
     * Generate realistic dimensions based on category
     */
    private function generateDimensions(string $category): array
    {
        $dimensionRanges = [
            'Electronics'   => ['length' => [10, 40], 'width' => [5, 30], 'height' => [1, 20]],
            'Fashion'       => ['length' => [20, 80], 'width' => [15, 60], 'height' => [2, 10]],
            'Home & Garden' => ['length' => [30, 200], 'width' => [20, 150], 'height' => [10, 100]],
            'Industrial'    => ['length' => [50, 300], 'width' => [40, 200], 'height' => [30, 150]],
            'Automotive'    => ['length' => [15, 100], 'width' => [10, 80], 'height' => [5, 50]],
        ];

        $ranges = $dimensionRanges[$category] ?? $dimensionRanges['Electronics'];

        return [
            'length' => $this->faker->randomFloat(2, $ranges['length'][0], $ranges['length'][1]),
            'width'  => $this->faker->randomFloat(2, $ranges['width'][0], $ranges['width'][1]),
            'height' => $this->faker->randomFloat(2, $ranges['height'][0], $ranges['height'][1]),
            'unit'   => 'cm',
        ];
    }

    /**
     * Create a product with existing relationships
     */
    public function withExistingRelationships(): static
    {
        return $this->state(function (array $attributes) {
            // Use existing users and categories if available
            $existingUser = User::inRandomOrder()->first();
            $existingCategory = Category::inRandomOrder()->first();

            return [
                'seller_id'   => $existingUser ? $existingUser->id : User::factory(),
                'category_id' => $existingCategory ? $existingCategory->id : Category::factory(),
            ];
        });
    }

    /**
     * Create featured products
     */
    public function featured(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_featured' => true,
                'is_approved' => true,
                'is_active'   => true,
            ];
        });
    }

    /**
     * Create products with samples
     */
    public function withSample(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'sample_available' => true,
                'sample_price'     => $this->faker->randomFloat(2, 5, 50),
            ];
        });
    }
}
