<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name'        => 'Electronics',
                'icon'        => 'pi pi-desktop',
                'description' => 'Latest electronics and gadgets',
                'children'    => [
                    'Smartphones' => ['iPhone', 'Samsung Galaxy', 'Google Pixel', 'OnePlus'],
                    'Laptops'     => ['Gaming Laptops', 'Business Laptops', 'Ultrabooks', 'Workstations'],
                    'Audio'       => ['Headphones', 'Speakers', 'Microphones', 'Sound Systems'],
                    'Components'  => ['Processors', 'Graphics Cards', 'Memory', 'Storage'],
                ],
            ],
            [
                'name'        => 'Fashion & Apparel',
                'icon'        => 'pi pi-shopping-bag',
                'description' => 'Clothing and fashion accessories',
                'children'    => [
                    'Men\'s Clothing'   => ['Shirts', 'Pants', 'Suits', 'Casual Wear'],
                    'Women\'s Clothing' => ['Dresses', 'Tops', 'Bottoms', 'Formal Wear'],
                    'Footwear'          => ['Sneakers', 'Boots', 'Sandals', 'Formal Shoes'],
                    'Accessories'       => ['Bags', 'Watches', 'Jewelry', 'Belts'],
                ],
            ],
            [
                'name'        => 'Home & Garden',
                'icon'        => 'pi pi-home',
                'description' => 'Home improvement and garden supplies',
                'children'    => [
                    'Furniture'  => ['Living Room', 'Bedroom', 'Office', 'Outdoor'],
                    'Appliances' => ['Kitchen', 'Laundry', 'Cleaning', 'HVAC'],
                    'Garden'     => ['Plants', 'Tools', 'Outdoor Furniture', 'Irrigation'],
                    'Decor'      => ['Lighting', 'Rugs', 'Wall Art', 'Curtains'],
                ],
            ],
            [
                'name'        => 'Sports & Outdoors',
                'icon'        => 'pi pi-bolt',
                'description' => 'Sports equipment and outdoor gear',
                'children'    => [
                    'Fitness'            => ['Gym Equipment', 'Yoga', 'Cardio', 'Weights'],
                    'Outdoor Recreation' => ['Camping', 'Hiking', 'Fishing', 'Hunting'],
                    'Team Sports'        => ['Football', 'Basketball', 'Soccer', 'Baseball'],
                    'Water Sports'       => ['Swimming', 'Surfing', 'Kayaking', 'Diving'],
                ],
            ],
            [
                'name'        => 'Automotive',
                'icon'        => 'pi pi-car',
                'description' => 'Auto parts and accessories',
                'children'    => [
                    'Parts'          => ['Engine', 'Transmission', 'Brakes', 'Suspension'],
                    'Accessories'    => ['Interior', 'Exterior', 'Electronics', 'Tools'],
                    'Maintenance'    => ['Oils', 'Filters', 'Fluids', 'Cleaning'],
                    'Tires & Wheels' => ['All Season', 'Winter', 'Performance', 'Rims'],
                ],
            ],
            [
                'name'        => 'Industrial Equipment',
                'icon'        => 'pi pi-cog',
                'description' => 'Industrial machinery and equipment',
                'children'    => [
                    'Manufacturing' => ['CNC Machines', 'Assembly Lines', 'Quality Control', 'Robotics'],
                    'Construction'  => ['Heavy Machinery', 'Hand Tools', 'Safety Equipment', 'Materials'],
                    'Electrical'    => ['Generators', 'Motors', 'Control Systems', 'Wiring'],
                    'HVAC'          => ['Air Conditioning', 'Heating', 'Ventilation', 'Refrigeration'],
                ],
            ],
        ];
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
            ]);

            foreach ($categoryData['children'] as $childName => $grandChildren) {
                $childCategory = Category::create([
                    'name'        => $childName,
                    'slug'        => str()->slug($childName),
                    'icon'        => null,
                    'description' => "Products related to {$childName}",
                    'parent_id'   => $parentCategory->id,
                    'level'       => 1,
                    'path'        => $parentCategory->id,
                    'status'      => 'active',
                ]);

                foreach ($grandChildren as $grandChildName) {
                    Category::create([
                        'name'        => $grandChildName,
                        'slug'        => str()->slug($grandChildName),
                        'icon'        => null,
                        'description' => "Specialized {$grandChildName} products",
                        'parent_id'   => $childCategory->id,
                        'level'       => 2,
                        'path'        => $parentCategory->id.'/'.$childCategory->id,
                        'status'      => 'active',
                    ]);
                }
            }
        }
    }
}
