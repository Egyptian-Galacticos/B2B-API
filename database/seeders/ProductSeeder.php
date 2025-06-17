<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sellers = User::role('seller')->where('status', 'active')->get();
        $categories = Category::whereNotNull('parent_id')->get();

        if ($sellers->isEmpty() || $categories->isEmpty()) {
            return;
        }

        // Create products for each seller
        foreach ($sellers as $seller) {
            $productCount = rand(3, 8); // Each seller gets 3-8 products

            for ($i = 0; $i < $productCount; $i++) {
                $category = $categories->random();
                $basePrice = rand(10, 1000);

                // Create product names based on category
                $productNames = $this->getProductNamesForCategory($category->name);
                $productName = fake()->randomElement($productNames);
                $product = Product::create([
                    'seller_id'      => $seller->id,
                    'category_id'    => $category->id,
                    'brand'          => fake()->randomElement(['TechCorp', 'InnovateLTD', 'ProManufacturing', 'GlobalSupply']),
                    'model_number'   => strtoupper(fake()->bothify('??###')),
                    'name'           => $productName,
                    'description'    => $this->generateProductDescription($productName, $category->name),
                    'sku'            => strtoupper(fake()->bothify('??###??')),
                    'price'          => $basePrice,
                    'currency'       => fake()->randomElement(['USD', 'EUR', 'GBP']),
                    'origin'         => fake()->randomElement(['USA', 'China', 'Germany', 'Japan', 'South Korea']),
                    'hs_code'        => fake()->numerify('########'),
                    'specifications' => [
                        'material'      => fake()->randomElement(['Steel', 'Aluminum', 'Plastic', 'Composite']),
                        'warranty'      => fake()->randomElement(['1 year', '2 years', '3 years']),
                        'certification' => fake()->randomElement(['CE', 'ISO', 'UL', 'FCC']),
                    ],
                    'dimensions' => [
                        'length' => rand(10, 100),
                        'width'  => rand(10, 100),
                        'height' => rand(5, 50),
                        'unit'   => 'cm',
                    ],
                    'sample_available' => fake()->boolean(40),
                    'sample_price'     => fake()->boolean(50) ? fake()->randomFloat(2, 5, 100) : 0,
                    'is_active'        => fake()->boolean(85),
                    'is_approved'      => fake()->boolean(70),
                    'is_featured'      => fake()->boolean(15),
                ]);

                $tags = fake()->words(rand(2, 5));
                $product->attachTags($tags);

                if (fake()->boolean(70)) {
                    $this->createPricingTiers($product);
                }
            }
        }
    }

    private function getProductNamesForCategory(string $categoryName): array
    {
        $productNames = [
            'iPhone'         => ['iPhone 15 Pro', 'iPhone 14', 'iPhone SE', 'iPhone 13 Mini'],
            'Samsung Galaxy' => ['Galaxy S24 Ultra', 'Galaxy A54', 'Galaxy Z Fold5', 'Galaxy Note20'],
            'Gaming Laptops' => ['ASUS ROG Strix', 'MSI Gaming Laptop', 'Alienware m15', 'HP Omen Gaming'],
            'Headphones'     => ['Sony WH-1000XM5', 'Bose QuietComfort', 'AirPods Pro', 'Sennheiser HD'],

            'Shirts'   => ['Cotton Dress Shirt', 'Casual Button-Down', 'Polo Shirt', 'Henley Shirt'],
            'Dresses'  => ['Summer Midi Dress', 'Cocktail Dress', 'Maxi Dress', 'Business Dress'],
            'Sneakers' => ['Nike Air Max', 'Adidas Ultraboost', 'Converse Chuck Taylor', 'Vans Old Skool'],

            'Living Room' => ['Sectional Sofa', 'Coffee Table', 'TV Stand', 'Accent Chair'],
            'Kitchen'     => ['Stainless Steel Refrigerator', 'Gas Range', 'Dishwasher', 'Microwave Oven'],

            'default' => ['Premium Product', 'Professional Grade', 'Commercial Quality', 'Industrial Strength'],
        ];

        return $productNames[$categoryName] ?? $productNames['default'];
    }

    private function generateProductDescription(string $productName, string $categoryName): string
    {
        $features = [
            'High-quality construction with premium materials',
            'Designed for professional use and durability',
            'Energy-efficient and environmentally friendly',
            'Easy to install and maintain',
            'Comes with comprehensive warranty',
            'Tested and certified to industry standards',
            'Available in multiple colors and sizes',
            'Perfect for both commercial and residential use',
        ];

        $selectedFeatures = fake()->randomElements($features, rand(3, 5));

        return "Professional grade {$productName} designed for {$categoryName} applications. ".
            implode('. ', $selectedFeatures).'. '.
            fake()->paragraph(2);
    }

    private function createPricingTiers(Product $product): void
    {
        $basePrice = $product->price;
        // Tier 1: 1-50 units
        PriceTier::create([
            'product_id'    => $product->id,
            'from_quantity' => 1,
            'to_quantity'   => 50,
            'price'         => $basePrice,
        ]);

        // Tier 2: 51-100 units (5% discount)
        PriceTier::create([
            'product_id'    => $product->id,
            'from_quantity' => 51,
            'to_quantity'   => 100,
            'price'         => round($basePrice * 0.95, 2),
        ]);

        // Tier 3: 101+ units (10% discount)
        if (fake()->boolean(60)) {
            PriceTier::create([
                'product_id'    => $product->id,
                'from_quantity' => 101,
                'to_quantity'   => 500,
                'price'         => round($basePrice * 0.90, 2),
            ]);
        }
    }
}
