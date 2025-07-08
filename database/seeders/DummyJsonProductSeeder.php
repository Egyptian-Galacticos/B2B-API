<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DummyJsonProductSeeder extends Seeder
{
    /**
     * Command instance for output
     */
    public $command;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting DummyJSON Product Seeder...');

        // Get existing B2B sellers from database
        $this->command->info('👥 Getting existing sellers from database...');
        $sellers = $this->getExistingSellers();

        if (empty($sellers)) {
            $this->command->warn('⚠️  No sellers found in database. Creating sample sellers...');
            $sellers = $this->createSellers();
        }

        $this->command->info('✅ Found '.count($sellers).' sellers');

        // Fetch all products from DummyJSON API
        $this->command->info('🌐 Fetching products from DummyJSON API...');
        $allProducts = $this->fetchAllProducts();

        if (empty($allProducts)) {
            $this->command->error('❌ Failed to fetch products from DummyJSON API');

            return;
        }

        $this->command->info('📦 Found '.count($allProducts).' products to import');

        $categoriesMap = [];
        $importedCount = 0;
        $failedCount = 0;
        $total = count($allProducts);

        // Import products with progress tracking
        $this->command->info('⚡ Importing products with images and price tiers...');

        foreach ($allProducts as $index => $productData) {
            try {
                $progress = $index + 1;
                $percentage = round(($progress / $total) * 100, 1);

                // Create/find category
                $category = $this->createOrFindCategory($productData['category'], $categoriesMap);

                // Create product with images
                $product = $this->createProduct($productData, $category, $sellers);

                // Add product images
                $this->addProductImages($product, $productData);

                // Create price tiers
                $this->createPriceTiers($product, $productData);

                $importedCount++;

                // Show progress every 10 products or at milestones
                if ($progress % 10 === 0 || $progress === $total || $progress <= 5) {
                    $this->command->info("✅ [{$percentage}%] Imported: {$product->name} (#{$progress}/{$total})");
                }

            } catch (\Exception $e) {
                $failedCount++;
                $this->command->error("❌ Failed to import product ID {$productData['id']}: ".$e->getMessage());
                Log::error('DummyJSON product import failed', [
                    'product_id' => $productData['id'],
                    'error'      => $e->getMessage(),
                    'trace'      => $e->getTraceAsString(),
                ]);
            }
        }

        // Final summary
        $this->command->info('');
        $this->command->info('🎉 Import Summary:');
        $this->command->info("   ✅ Successfully imported: {$importedCount} products");
        $this->command->info("   ❌ Failed imports: {$failedCount} products");
        $this->command->info('   📂 Categories created: '.count($categoriesMap));
        $this->command->info('   🏢 Sellers with companies: '.count($sellers));

        if ($failedCount > 0) {
            $this->command->warn("⚠️  {$failedCount} products failed to import. Check logs for details.");
        }

        $this->command->info('🏁 DummyJSON Product Seeder completed successfully!');
    }

    /**
     * Fetch all products from DummyJSON API
     */
    private function fetchAllProducts(): array
    {
        $allProducts = [];
        $limit = 30; // API limit per request
        $skip = 0;
        $total = null;

        do {
            try {
                $this->command->info("Fetching products: skip={$skip}, limit={$limit}");

                $response = Http::timeout(30)->get('https://dummyjson.com/products', [
                    'limit' => $limit,
                    'skip'  => $skip,
                ]);

                if (! $response->successful()) {
                    throw new \Exception('API request failed with status: '.$response->status());
                }

                $data = $response->json();

                if (! isset($data['products'])) {
                    throw new \Exception('Invalid API response structure');
                }

                $allProducts = array_merge($allProducts, $data['products']);

                if ($total === null) {
                    $total = $data['total'] ?? 0;
                    $this->command->info("Total products available: {$total}");
                }

                $skip += $limit;

                // Small delay to be respectful to the API
                usleep(200000); // 200ms delay

            } catch (\Exception $e) {
                $this->command->error("Failed to fetch products at skip={$skip}: ".$e->getMessage());
                break;
            }

        } while ($skip < $total && count($allProducts) < $total);

        return $allProducts;
    }

    /**
     * Create realistic B2B sellers with comprehensive company information (fallback only)
     */
    private function createSellers(): array
    {
        $sellers = [];

        $sellerData = [
            [
                'user' => [
                    'first_name'   => 'James',
                    'last_name'    => 'Anderson',
                    'email'        => 'j.anderson@globalsupply.com',
                    'phone_number' => '+1-555-101-2001',
                ],
                'company' => [
                    'name'                    => 'Global Supply Chain Inc.',
                    'email'                   => 'contact@globalsupply.com',
                    'tax_id'                  => 'GSC-2023-001',
                    'commercial_registration' => 'CR-12345678',
                    'company_phone'           => '+1-555-101-2000',
                    'website'                 => 'https://globalsupply.com',
                    'description'             => 'Leading wholesale distributor specializing in electronics, industrial components, and consumer goods with over 20 years of experience.',
                    'address'                 => [
                        'street'      => '1200 Industrial Boulevard',
                        'city'        => 'Los Angeles',
                        'state'       => 'California',
                        'country'     => 'United States',
                        'postal_code' => '90210',
                    ],
                ],
            ],
            [
                'user' => [
                    'first_name'   => 'Sarah',
                    'last_name'    => 'Chen',
                    'email'        => 's.chen@techcorp.com',
                    'phone_number' => '+1-555-202-3001',
                ],
                'company' => [
                    'name'                    => 'TechCorp Solutions Ltd.',
                    'email'                   => 'sales@techcorp.com',
                    'tax_id'                  => 'TCS-2023-002',
                    'commercial_registration' => 'CR-23456789',
                    'company_phone'           => '+1-555-202-3000',
                    'website'                 => 'https://techcorp.com',
                    'description'             => 'Premier electronics manufacturer and technology solutions provider serving Fortune 500 companies worldwide.',
                    'address'                 => [
                        'street'      => '500 Silicon Valley Drive',
                        'city'        => 'San Francisco',
                        'state'       => 'California',
                        'country'     => 'United States',
                        'postal_code' => '94102',
                    ],
                ],
            ],
            [
                'user' => [
                    'first_name'   => 'Michael',
                    'last_name'    => 'Rodriguez',
                    'email'        => 'm.rodriguez@promanufacturing.com',
                    'phone_number' => '+1-555-303-4001',
                ],
                'company' => [
                    'name'                    => 'ProManufacturing Company',
                    'email'                   => 'orders@promanufacturing.com',
                    'tax_id'                  => 'PMC-2023-003',
                    'commercial_registration' => 'CR-34567890',
                    'company_phone'           => '+1-555-303-4000',
                    'website'                 => 'https://promanufacturing.com',
                    'description'             => 'Industrial manufacturing specialist producing high-quality machinery, tools, and automotive components.',
                    'address'                 => [
                        'street'      => '800 Manufacturing Way',
                        'city'        => 'Detroit',
                        'state'       => 'Michigan',
                        'country'     => 'United States',
                        'postal_code' => '48201',
                    ],
                ],
            ],
            [
                'user' => [
                    'first_name'   => 'Emily',
                    'last_name'    => 'Johnson',
                    'email'        => 'e.johnson@innovateltd.com',
                    'phone_number' => '+1-555-404-5001',
                ],
                'company' => [
                    'name'                    => 'Innovate Limited',
                    'email'                   => 'business@innovateltd.com',
                    'tax_id'                  => 'INL-2023-004',
                    'commercial_registration' => 'CR-45678901',
                    'company_phone'           => '+1-555-404-5000',
                    'website'                 => 'https://innovateltd.com',
                    'description'             => 'Cutting-edge technology solutions provider specializing in smart home devices, IoT systems, and renewable energy products.',
                    'address'                 => [
                        'street'      => '300 Innovation Plaza',
                        'city'        => 'Austin',
                        'state'       => 'Texas',
                        'country'     => 'United States',
                        'postal_code' => '73301',
                    ],
                ],
            ],
            [
                'user' => [
                    'first_name'   => 'David',
                    'last_name'    => 'Kim',
                    'email'        => 'd.kim@qualitygoods.com',
                    'phone_number' => '+1-555-505-6001',
                ],
                'company' => [
                    'name'                    => 'QualityGoods Trading LLC',
                    'email'                   => 'sales@qualitygoods.com',
                    'tax_id'                  => 'QGT-2023-005',
                    'commercial_registration' => 'CR-56789012',
                    'company_phone'           => '+1-555-505-6000',
                    'website'                 => 'https://qualitygoods.com',
                    'description'             => 'International trading company specializing in import/export of consumer goods, textiles, and home furnishings.',
                    'address'                 => [
                        'street'      => '150 Trade Center Blvd',
                        'city'        => 'Miami',
                        'state'       => 'Florida',
                        'country'     => 'United States',
                        'postal_code' => '33101',
                    ],
                ],
            ],
        ];

        foreach ($sellerData as $data) {
            // Create or find user
            $seller = User::firstOrCreate(
                ['email' => $data['user']['email']],
                [
                    'first_name'        => $data['user']['first_name'],
                    'last_name'         => $data['user']['last_name'],
                    'password'          => bcrypt('password123'),
                    'email_verified_at' => now(),
                    'phone_number'      => $data['user']['phone_number'],
                    'is_email_verified' => true,
                    'status'            => 'active',
                ]
            );

            // Assign seller role if not already assigned
            if (! $seller->hasRole('seller')) {
                $seller->assignRole('seller');
            }

            // Create or update company
            if (! $seller->company) {
                $company = Company::create([
                    'user_id'                 => $seller->id,
                    'name'                    => $data['company']['name'],
                    'email'                   => $data['company']['email'],
                    'tax_id'                  => $data['company']['tax_id'],
                    'commercial_registration' => $data['company']['commercial_registration'],
                    'company_phone'           => $data['company']['company_phone'],
                    'website'                 => $data['company']['website'],
                    'description'             => $data['company']['description'],
                    'address'                 => $data['company']['address'],
                    'is_email_verified'       => true,
                ]);

                // Add company logo
                $this->addCompanyLogo($company, $data['company']['name']);
            }

            $sellers[] = $seller;
        }

        return $sellers;
    }

    /**
     * Get existing sellers from the database
     */
    private function getExistingSellers(): array
    {
        // Get users with seller role who have companies
        $sellers = User::whereHas('roles', function ($query) {
            $query->where('name', 'seller');
        })
            ->whereHas('company') // Only sellers with companies
            ->with('company') // Load company relationship
            ->get();

        return $sellers->toArray() ? $sellers->all() : [];
    }

    /**
     * Create or find category
     */
    private function createOrFindCategory(string $categoryName, array &$categoriesMap): Category
    {
        if (isset($categoriesMap[$categoryName])) {
            return $categoriesMap[$categoryName];
        }

        // Map DummyJSON categories to more descriptive names
        $categoryMap = [
            'smartphones'         => 'Smartphones',
            'laptops'             => 'Laptops',
            'fragrances'          => 'Fragrances & Perfumes',
            'skincare'            => 'Skincare & Beauty',
            'groceries'           => 'Groceries & Food',
            'home-decoration'     => 'Home Decoration',
            'kitchen-accessories' => 'Kitchen Accessories',
            'furniture'           => 'Furniture',
            'tops'                => 'Clothing - Tops',
            'womens-dresses'      => 'Women\'s Dresses',
            'womens-shoes'        => 'Women\'s Shoes',
            'mens-shirts'         => 'Men\'s Shirts',
            'mens-shoes'          => 'Men\'s Shoes',
            'mens-watches'        => 'Men\'s Watches',
            'womens-watches'      => 'Women\'s Watches',
            'womens-bags'         => 'Women\'s Bags',
            'womens-jewellery'    => 'Women\'s Jewellery',
            'sunglasses'          => 'Sunglasses',
            'automotive'          => 'Automotive',
            'motorcycle'          => 'Motorcycle',
            'lighting'            => 'Lighting',
        ];

        $displayName = $categoryMap[$categoryName] ?? ucwords(str_replace('-', ' ', $categoryName));

        $category = Category::firstOrCreate(
            ['name' => $displayName],
            [
                'slug'        => Str::slug($displayName),
                'description' => $this->generateCategoryDescription($displayName),
                'status'      => 'active',
            ]
        );

        $categoriesMap[$categoryName] = $category;

        return $category;
    }

    /**
     * Create product from DummyJSON data
     */
    private function createProduct(array $productData, Category $category, array $sellers): Product
    {
        $seller = $sellers[array_rand($sellers)];

        // Generate realistic B2B data
        $dummyDimensions = $productData['dimensions'] ?? [];
        $weight = $productData['weight'] ?? rand(1, 20);

        // Generate realistic dimensions based on category and weight
        $dimensions = $this->generateRealisticDimensions($category->name, $weight, $dummyDimensions);

        // Calculate realistic price from DummyJSON data for price tiers
        $basePrice = $productData['price'] ?? rand(10, 500);
        $discountPercentage = $productData['discountPercentage'] ?? 0;
        $finalPrice = $basePrice * (1 - $discountPercentage / 100);

        // Determine sample availability (80% chance)
        $sampleAvailable = rand(1, 5) <= 4;
        $samplePrice = $sampleAvailable ? rand(5, 50) : 0.00;

        $product = Product::create([
            'name'             => $productData['title'],
            'slug'             => Str::slug($productData['title'].'-'.$productData['id']),
            'description'      => $this->enhanceProductDescription($productData['description'], $category->name),
            'category_id'      => $category->id,
            'seller_id'        => $seller->id,
            'sku'              => $this->generateRealisticSku($category->name, $productData),
            'brand'            => $this->generateRealisticBrand($category->name),
            'model_number'     => $this->generateModelNumber($productData['title']),
            'weight'           => $weight,
            'dimensions'       => $dimensions,
            'origin'           => $this->getRandomOrigin(),
            'currency'         => 'USD',
            'hs_code'          => $this->generateHsCode($category->name),
            'is_active'        => true,
            'is_approved'      => true,
            'is_featured'      => rand(1, 10) === 1, // 10% chance of being featured
            'sample_available' => $sampleAvailable,
            'sample_price'     => $samplePrice, // Only set if sample is available
            'created_at'       => now()->subDays(rand(0, 90)),
            'updated_at'       => now()->subDays(rand(0, 30)),
        ]);

        // Store the calculated price for price tiers
        $product->calculated_price = round($finalPrice, 2);

        // Add realistic product tags
        $this->addProductTags($product, $category->name, $productData);

        // Handle product images from DummyJSON API
        $this->addProductImages($product, $productData);

        return $product;
    }

    /**
     * Create realistic price tiers
     */
    private function createPriceTiers(Product $product, array $productData): void
    {
        // Use the calculated price stored on the product
        $unitPrice = $product->calculated_price ?? $productData['price'] ?? rand(10, 500);

        // Create multiple price tiers for B2B
        $tiers = [
            [
                'min_quantity'        => 1,
                'max_quantity'        => 9,
                'price'               => round($unitPrice, 2),
                'discount_percentage' => 0,
            ],
            [
                'min_quantity'        => 10,
                'max_quantity'        => 49,
                'price'               => round($unitPrice * 0.95, 2), // 5% bulk discount
                'discount_percentage' => 5,
            ],
            [
                'min_quantity'        => 50,
                'max_quantity'        => 99,
                'price'               => round($unitPrice * 0.90, 2), // 10% bulk discount
                'discount_percentage' => 10,
            ],
            [
                'min_quantity'        => 100,
                'max_quantity'        => null,
                'price'               => round($unitPrice * 0.85, 2), // 15% bulk discount
                'discount_percentage' => 15,
            ],
        ];

        foreach ($tiers as $tier) {
            PriceTier::create([
                'product_id'    => $product->id,
                'from_quantity' => $tier['min_quantity'],
                'to_quantity'   => $tier['max_quantity'] ?? 999999, // Use large number if null
                'price'         => $tier['price'],
                'currency'      => 'USD',
            ]);
        }
    }

    /**
     * Generate realistic brand based on category
     */
    private function generateRealisticBrand(string $categoryName): string
    {
        $brandMap = [
            'Smartphones'         => ['Samsung', 'Apple', 'Huawei', 'Xiaomi', 'OnePlus', 'Google', 'Sony'],
            'Laptops'             => ['Dell', 'HP', 'Lenovo', 'Apple', 'ASUS', 'Acer', 'MSI'],
            'Kitchen Accessories' => ['KitchenAid', 'Cuisinart', 'OXO', 'All-Clad', 'Lodge', 'Ninja'],
            'Home Decoration'     => ['IKEA', 'West Elm', 'Pottery Barn', 'CB2', 'Target', 'HomeGoods'],
            'Groceries & Food'    => ['Organic Valley', 'Simply Organic', 'Nature\'s Path', 'Bob\'s Red Mill'],
            'Clothing - Tops'     => ['Nike', 'Adidas', 'H&M', 'Zara', 'Uniqlo', 'Gap'],
            'Women\'s Shoes'      => ['Nike', 'Adidas', 'Steve Madden', 'Nine West', 'Jessica Simpson'],
            'Men\'s Shoes'        => ['Nike', 'Adidas', 'Timberland', 'Dr. Martens', 'Vans'],
            'Automotive'          => ['Bosch', 'Castrol', 'Mobil 1', 'Valvoline', 'ACDelco'],
        ];

        $brands = $brandMap[$categoryName] ?? ['ProBrand', 'QualityMaker', 'TechCorp', 'GlobalSupply', 'InnovateLTD'];

        return $brands[array_rand($brands)];
    }

    /**
     * Generate model number
     */
    private function generateModelNumber(string $productName): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $productName), 0, 3));
        $number = rand(100, 999);
        $suffix = strtoupper(Str::random(2));

        return "{$prefix}-{$number}-{$suffix}";
    }

    /**
     * Get random origin country
     */
    private function getRandomOrigin(): string
    {
        $origins = [
            'China', 'United States', 'Germany', 'Japan', 'South Korea',
            'Taiwan', 'India', 'Vietnam', 'Thailand', 'Malaysia',
            'Italy', 'France', 'United Kingdom', 'Canada', 'Mexico',
        ];

        return $origins[array_rand($origins)];
    }

    /**
     * Generate category description
     */
    private function generateCategoryDescription(string $categoryName): string
    {
        return "Professional-grade {$categoryName} for business and commercial use. Our {$categoryName} collection offers reliable, high-quality products suitable for bulk purchasing and wholesale distribution.";
    }

    /**
     * Generate HS Code based on category
     */
    private function generateHsCode(string $categoryName): string
    {
        $hsCodeMap = [
            'Smartphones'           => '8517.12.00',
            'Laptops'               => '8471.30.01',
            'Kitchen Accessories'   => '8211.91.50',
            'Groceries & Food'      => '2106.90.99',
            'Automotive'            => '8708.99.81',
            'Fragrances & Perfumes' => '3303.00.30',
            'Sports Accessories'    => '9506.99.60',
            'default'               => '9999.00.00',
        ];

        return $hsCodeMap[$categoryName] ?? $hsCodeMap['default'];
    }

    /**
     * Add product images from DummyJSON API data
     */
    private function addProductImages(Product $product, array $productData): void
    {
        try {
            // Add main image (thumbnail)
            if (isset($productData['thumbnail']) && filter_var($productData['thumbnail'], FILTER_VALIDATE_URL)) {
                $product
                    ->addMediaFromUrl($productData['thumbnail'])
                    ->usingName($product->name.' - Main Image')
                    ->toMediaCollection('main_image');
            }

            // Add additional product images
            if (isset($productData['images']) && is_array($productData['images'])) {
                foreach ($productData['images'] as $index => $imageUrl) {
                    if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                        $product
                            ->addMediaFromUrl($imageUrl)
                            ->usingName($product->name.' - Image '.($index + 1))
                            ->toMediaCollection('product_images');
                    }
                }
            }
        } catch (\Exception $e) {
            // Log image download errors but continue with product creation
            Log::warning('Failed to download image for product: '.$product->name, [
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate random address
     */
    private function generateRandomAddress(): array
    {
        $cities = [
            ['city' => 'New York', 'state' => 'NY', 'zip' => '10001'],
            ['city' => 'Los Angeles', 'state' => 'CA', 'zip' => '90210'],
            ['city' => 'Chicago', 'state' => 'IL', 'zip' => '60601'],
            ['city' => 'Houston', 'state' => 'TX', 'zip' => '77001'],
            ['city' => 'Miami', 'state' => 'FL', 'zip' => '33101'],
        ];

        $location = $cities[array_rand($cities)];
        $streetNumber = rand(100, 9999);
        $streets = ['Main St', 'Business Blvd', 'Commerce Ave', 'Industrial Way', 'Corporate Dr'];

        return [
            'street'   => $streetNumber.' '.$streets[array_rand($streets)],
            'city'     => $location['city'],
            'state'    => $location['state'],
            'zip_code' => $location['zip'],
            'country'  => 'United States',
        ];
    }

    /**
     * Add logo to company using placeholder or generated logo
     */
    private function addCompanyLogo(Company $company, string $companyName): void
    {
        try {
            // Generate a simple company logo using a logo API service
            $logoUrl = $this->generateLogoUrl($companyName);

            if ($logoUrl) {
                $response = Http::timeout(15)->get($logoUrl);

                if ($response->successful()) {
                    $tempPath = tempnam(sys_get_temp_dir(), 'company_logo_');
                    file_put_contents($tempPath, $response->body());

                    $company->addMedia($tempPath)
                        ->usingName($companyName.' Logo')
                        ->usingFileName('logo.png')
                        ->toMediaCollection('logo');

                    @unlink($tempPath);

                    $this->command->info("Added logo for company: {$companyName}");
                }
            }
        } catch (\Exception $e) {
            $this->command->warn("Failed to add logo for company {$companyName}: ".$e->getMessage());
            Log::warning('Company logo failed', [
                'company_name' => $companyName,
                'error'        => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate logo URL for company
     */
    private function generateLogoUrl(string $companyName): string
    {
        // Use a simple placeholder service for company logos
        $encodedName = urlencode($companyName);
        $colors = ['4285f4', '34a853', 'fbbc05', 'ea4335', '9c27b0', '00bcd4'];
        $bgColor = $colors[array_rand($colors)];

        // Using a simple text-to-image service
        return "https://via.placeholder.com/300x300/{$bgColor}/ffffff?text=".$encodedName;
    }

    /**
     * Generate realistic dimensions based on category and weight
     */
    private function generateRealisticDimensions(string $categoryName, float $weight, array $dummyDimensions = []): array
    {
        // Category-specific dimension patterns
        $dimensionPatterns = [
            'Smartphones'           => ['length' => [13, 17], 'width' => [6, 8], 'height' => [0.7, 1.2]],
            'Laptops'               => ['length' => [30, 40], 'width' => [20, 30], 'height' => [1.5, 3]],
            'Kitchen Accessories'   => ['length' => [15, 35], 'width' => [10, 25], 'height' => [5, 20]],
            'Fragrances & Perfumes' => ['length' => [3, 8], 'width' => [3, 8], 'height' => [8, 15]],
            'Skincare & Beauty'     => ['length' => [4, 12], 'width' => [4, 12], 'height' => [8, 20]],
            'Groceries & Food'      => ['length' => [8, 25], 'width' => [5, 20], 'height' => [10, 30]],
            'Home Decoration'       => ['length' => [10, 50], 'width' => [10, 40], 'height' => [15, 60]],
            'Furniture'             => ['length' => [60, 200], 'width' => [40, 120], 'height' => [40, 180]],
            'Clothing - Tops'       => ['length' => [40, 80], 'width' => [30, 60], 'height' => [2, 5]],
            'Women\'s Shoes'        => ['length' => [22, 28], 'width' => [8, 12], 'height' => [3, 15]],
            'Men\'s Shoes'          => ['length' => [25, 32], 'width' => [9, 13], 'height' => [3, 12]],
            'Automotive'            => ['length' => [15, 80], 'width' => [10, 40], 'height' => [5, 30]],
        ];

        $pattern = $dimensionPatterns[$categoryName] ?? ['length' => [10, 50], 'width' => [10, 40], 'height' => [5, 30]];

        // Use DummyJSON dimensions as base if available, otherwise generate based on weight/category
        $length = $dummyDimensions['width'] ?? rand($pattern['length'][0], $pattern['length'][1]);
        $width = $dummyDimensions['depth'] ?? rand($pattern['width'][0], $pattern['width'][1]);
        $height = $dummyDimensions['height'] ?? rand($pattern['height'][0], $pattern['height'][1]);

        // Adjust dimensions based on weight for realism
        if ($weight > 10) {
            $length *= 1.2;
            $width *= 1.2;
            $height *= 1.1;
        } elseif ($weight < 2) {
            $length *= 0.8;
            $width *= 0.8;
            $height *= 0.9;
        }

        return [
            'length' => round($length, 1),
            'width'  => round($width, 1),
            'height' => round($height, 1),
            'unit'   => 'cm',
        ];
    }

    /**
     * Enhance product description with B2B-specific details
     */
    private function enhanceProductDescription(string $originalDescription, string $categoryName): string
    {
        $b2bFeatures = [
            'Bulk pricing available for large orders',
            'Professional-grade quality and durability',
            'Suitable for commercial and industrial use',
            'Comprehensive warranty and support included',
            'Fast shipping and reliable supply chain',
            'Customization options available for bulk orders',
            'Certified to international quality standards',
            'Eco-friendly and sustainable manufacturing',
        ];

        $selectedFeatures = array_rand(array_flip($b2bFeatures), rand(1, 3));
        if (! is_array($selectedFeatures)) {
            $selectedFeatures = [$selectedFeatures];
        }

        $enhancement = ' '.implode('. ', $selectedFeatures).'.';

        return $originalDescription.$enhancement;
    }

    /**
     * Generate realistic SKU based on category and product data
     */
    private function generateRealisticSku(string $categoryName, array $productData): string
    {
        // Use existing SKU if available
        if (! empty($productData['sku'])) {
            return $productData['sku'];
        }

        // Category prefixes for SKUs
        $categoryPrefixes = [
            'Smartphones'           => 'PHN',
            'Laptops'               => 'LPT',
            'Kitchen Accessories'   => 'KIT',
            'Fragrances & Perfumes' => 'FRG',
            'Skincare & Beauty'     => 'SKN',
            'Groceries & Food'      => 'GRO',
            'Home Decoration'       => 'HOM',
            'Furniture'             => 'FUR',
            'Clothing - Tops'       => 'CLT',
            'Women\'s Shoes'        => 'WSH',
            'Men\'s Shoes'          => 'MSH',
            'Automotive'            => 'AUT',
        ];

        $prefix = $categoryPrefixes[$categoryName] ?? 'PRD';
        $middle = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $productData['title'] ?? 'PRODUCT'), 0, 3));
        $suffix = str_pad($productData['id'] ?? rand(100, 999), 3, '0', STR_PAD_LEFT);

        return "{$prefix}-{$middle}-{$suffix}";
    }

    /**
     * Add realistic product tags based on category and product data
     */
    private function addProductTags(Product $product, string $categoryName, array $productData): void
    {
        // Base tags from category
        $categoryTags = $this->getCategoryTags($categoryName);

        // Additional tags from product characteristics
        $characteristicTags = $this->getCharacteristicTags($productData);

        // Business-related tags
        $businessTags = [
            'wholesale', 'bulk-order', 'b2b', 'commercial', 'professional',
        ];

        // Combine all tags
        $allTags = array_merge($categoryTags, $characteristicTags, $businessTags);

        // Select 3-6 random tags to avoid over-tagging
        $selectedTags = array_rand(array_flip($allTags), rand(3, 6));
        if (! is_array($selectedTags)) {
            $selectedTags = [$selectedTags];
        }

        // Attach tags to product
        $product->attachTags($selectedTags);
    }

    /**
     * Get category-specific tags
     */
    private function getCategoryTags(string $categoryName): array
    {
        $categoryTagMap = [
            'Smartphones'           => ['mobile', 'communication', 'technology', 'wireless', 'smart-device'],
            'Laptops'               => ['computer', 'portable', 'technology', 'business', 'office'],
            'Kitchen Accessories'   => ['kitchen', 'cooking', 'utensil', 'food-prep', 'culinary'],
            'Fragrances & Perfumes' => ['fragrance', 'cosmetic', 'beauty', 'personal-care', 'luxury'],
            'Skincare & Beauty'     => ['skincare', 'beauty', 'cosmetic', 'health', 'personal-care'],
            'Groceries & Food'      => ['food', 'grocery', 'consumable', 'nutrition', 'organic'],
            'Home Decoration'       => ['home', 'decor', 'interior', 'furniture', 'design'],
            'Furniture'             => ['furniture', 'home', 'interior', 'comfort', 'storage'],
            'Clothing - Tops'       => ['clothing', 'apparel', 'fashion', 'textile', 'wear'],
            'Women\'s Shoes'        => ['footwear', 'shoes', 'fashion', 'women', 'style'],
            'Men\'s Shoes'          => ['footwear', 'shoes', 'fashion', 'men', 'style'],
            'Women\'s Watches'      => ['watch', 'timepiece', 'accessory', 'women', 'jewelry'],
            'Men\'s Watches'        => ['watch', 'timepiece', 'accessory', 'men', 'jewelry'],
            'Automotive'            => ['automotive', 'car', 'vehicle', 'transport', 'maintenance'],
            'Sunglasses'            => ['eyewear', 'sunglasses', 'protection', 'fashion', 'accessory'],
        ];

        return $categoryTagMap[$categoryName] ?? ['product', 'merchandise', 'item'];
    }

    /**
     * Get tags based on product characteristics
     */
    private function getCharacteristicTags(array $productData): array
    {
        $tags = [];

        // Add brand-related tag if available
        if (! empty($productData['brand'])) {
            $tags[] = strtolower(str_replace(' ', '-', $productData['brand']));
        }

        // Add rating-related tags
        if (isset($productData['rating']) && $productData['rating'] >= 4.5) {
            $tags[] = 'premium';
            $tags[] = 'high-quality';
        } elseif (isset($productData['rating']) && $productData['rating'] >= 4.0) {
            $tags[] = 'quality';
        }

        // Add discount-related tags
        if (isset($productData['discountPercentage']) && $productData['discountPercentage'] > 20) {
            $tags[] = 'discounted';
            $tags[] = 'sale';
        } elseif (isset($productData['discountPercentage']) && $productData['discountPercentage'] > 10) {
            $tags[] = 'offer';
        }

        // Add stock-related tags
        if (isset($productData['stock']) && $productData['stock'] > 100) {
            $tags[] = 'in-stock';
            $tags[] = 'available';
        }

        // Add price-related tags
        $price = $productData['price'] ?? 0;
        if ($price > 500) {
            $tags[] = 'premium';
            $tags[] = 'luxury';
        } elseif ($price > 100) {
            $tags[] = 'mid-range';
        } else {
            $tags[] = 'affordable';
            $tags[] = 'budget';
        }

        return $tags;
    }
}
