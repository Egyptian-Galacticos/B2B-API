<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

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

        foreach ($sellers as $seller) {
            $productCount = rand(8, 15);
            for ($i = 0; $i < $productCount; $i++) {
                $category = $categories->random();
                $basePrice = rand(10, 1000);

                $productNames = $this->getProductNamesForCategory($category->name);
                $productName = fake()->randomElement($productNames);
                $product = Product::create([
                    'seller_id'    => $seller->id,
                    'category_id'  => $category->id,
                    'brand'        => fake()->randomElement(['TechCorp', 'InnovateLTD', 'ProManufacturing', 'GlobalSupply']),
                    'model_number' => strtoupper(fake()->bothify('??###')),
                    'name'         => $productName,
                    'description'  => $this->generateProductDescription($productName, $category->name),
                    'sku'          => strtoupper(fake()->bothify('??###??')),
                    'weight'       => $basePrice,
                    'currency'     => fake()->randomElement(['USD', 'EUR', 'GBP']),
                    'origin'       => fake()->randomElement(['USA', 'China', 'Germany', 'Japan', 'South Korea']),
                    'hs_code'      => fake()->numerify('########'),
                    'dimensions'   => [
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

                $this->addProductImages($product, $category->name);

                if (fake()->boolean(70)) {
                    $this->createPricingTiers($product);
                }
            }
        }
    }

    /**
     * Add images to product efficiently using local placeholder images
     */
    private function addProductImages(Product $product, string $categoryName): void
    {
        $imagePath = $this->getLocalProductImagePath($categoryName);

        try {
            if (fake()->boolean(90) && $imagePath && file_exists($imagePath)) {
                // Copy the file to a temporary location to avoid it being moved
                $tempPath = storage_path('app/temp_'.uniqid().'_'.basename($imagePath));
                copy($imagePath, $tempPath);

                $product->addMedia($tempPath)
                    ->toMediaCollection('main_image');
            }

            if (fake()->boolean(60) && $imagePath && file_exists($imagePath)) {
                $additionalImageCount = rand(1, 2);
                for ($i = 0; $i < $additionalImageCount; $i++) {
                    $tempPath = storage_path('app/temp_'.uniqid().'_'.basename($imagePath));
                    copy($imagePath, $tempPath);

                    $product->addMedia($tempPath)
                        ->toMediaCollection('product_images');
                }
            }
        } catch (Exception $e) {
            Log::warning("Failed to add image to product {$product->id}: ".$e->getMessage());
        }
    }

    /**
     * Get local image path for product based on category
     */
    private function getLocalProductImagePath(string $categoryName): ?string
    {
        $basePath = storage_path('app/public/placeholders/');

        $imageMap = [
            'iPhone'           => 'electronics.jpg',
            'Samsung Galaxy'   => 'electronics.jpg',
            'Gaming Laptops'   => 'electronics.jpg',
            'Business Laptops' => 'electronics.jpg',
            'Headphones'       => 'electronics.jpg',
            'Speakers'         => 'electronics.jpg',

            'Shirts'   => 'fashion.jpg',
            'Pants'    => 'fashion.jpg',
            'Dresses'  => 'fashion.jpg',
            'Tops'     => 'fashion.jpg',
            'Sneakers' => 'fashion.jpg',
            'Boots'    => 'fashion.jpg',

            'Living Room' => 'home.jpg',
            'Bedroom'     => 'home.jpg',
            'Kitchen'     => 'home.jpg',
            'Laundry'     => 'home.jpg',
            'Plants'      => 'home.jpg',
            'Tools'       => 'home.jpg',

            'Gym Equipment' => 'sports.jpg',
            'Yoga'          => 'sports.jpg',
            'Camping'       => 'sports.jpg',
            'Hiking'        => 'sports.jpg',

            'Engine'   => 'automotive.jpg',
            'Brakes'   => 'automotive.jpg',
            'Interior' => 'automotive.jpg',
            'Exterior' => 'automotive.jpg',
        ];

        $filename = $imageMap[$categoryName] ?? 'default.jpg';

        return $basePath.$filename;
    }

    /**
     * Map category names to image categories
     */
    private function getImageCategory(string $categoryName): string
    {
        $categoryMappings = [
            'iPhone'         => 'electronics',
            'Samsung Galaxy' => 'electronics',
            'Gaming Laptops' => 'electronics',
            'Headphones'     => 'electronics',
            'Shirts'         => 'clothing',
            'Dresses'        => 'clothing',
            'Sneakers'       => 'clothing',
            'Living Room'    => 'furniture',
            'Kitchen'        => 'furniture',
        ];

        return $categoryMappings[$categoryName] ?? 'default';
    }

    private function getProductNamesForCategory(string $categoryName): array
    {
        $productNames = [
            'iPhone'           => ['iPhone 15 Pro', 'iPhone 14', 'iPhone SE'],
            'Samsung Galaxy'   => ['Galaxy S24 Ultra', 'Galaxy A54', 'Galaxy Z Fold5'],
            'Gaming Laptops'   => ['ASUS ROG Strix', 'MSI Gaming Laptop', 'Alienware m15'],
            'Business Laptops' => ['ThinkPad X1', 'MacBook Pro', 'Dell XPS'],
            'Headphones'       => ['Sony WH-1000XM5', 'Bose QuietComfort', 'AirPods Pro'],
            'Speakers'         => ['JBL Charge', 'Bose SoundLink', 'Sony SRS'],

            'Shirts'   => ['Cotton Dress Shirt', 'Casual Button-Down', 'Polo Shirt'],
            'Pants'    => ['Chino Pants', 'Dress Pants', 'Cargo Pants'],
            'Dresses'  => ['Summer Midi Dress', 'Cocktail Dress', 'Maxi Dress'],
            'Tops'     => ['Blouse', 'Tank Top', 'Sweater'],
            'Sneakers' => ['Nike Air Max', 'Adidas Ultraboost', 'Converse Chuck Taylor'],
            'Boots'    => ['Work Boots', 'Hiking Boots', 'Fashion Boots'],

            'Living Room' => ['Sectional Sofa', 'Coffee Table', 'TV Stand'],
            'Bedroom'     => ['Queen Bed', 'Nightstand', 'Dresser'],
            'Kitchen'     => ['Refrigerator', 'Gas Range', 'Dishwasher'],
            'Laundry'     => ['Washing Machine', 'Dryer', 'Laundry Basket'],
            'Plants'      => ['Indoor Plants', 'Garden Plants', 'Succulents'],
            'Tools'       => ['Garden Tools', 'Hand Tools', 'Power Tools'],

            'Gym Equipment' => ['Dumbbells', 'Treadmill', 'Exercise Bike'],
            'Yoga'          => ['Yoga Mat', 'Yoga Blocks', 'Yoga Straps'],
            'Camping'       => ['Tent', 'Sleeping Bag', 'Camping Stove'],
            'Hiking'        => ['Hiking Boots', 'Backpack', 'Hiking Poles'],

            'Engine'   => ['Engine Parts', 'Engine Oil', 'Air Filter'],
            'Brakes'   => ['Brake Pads', 'Brake Discs', 'Brake Fluid'],
            'Interior' => ['Seat Covers', 'Floor Mats', 'Dashboard'],
            'Exterior' => ['Car Wax', 'Bumper Guards', 'Headlights'],

            'default' => ['Premium Product', 'Professional Grade', 'Commercial Quality'],
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
        $basePrice = $product->weight;
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
