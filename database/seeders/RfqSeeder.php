<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Rfq;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RfqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activeBuyers = User::role('buyer')->where('status', 'active')->get();
        $activeSellers = User::role('seller')->where('status', 'active')->get();
        $activeProducts = Product::where('is_active', true)->get();

        if ($activeBuyers->isEmpty() || $activeSellers->isEmpty() || $activeProducts->isEmpty()) {
            return;
        }

        $statusDistribution = [
            Rfq::STATUS_PENDING     => 35,
            Rfq::STATUS_SEEN        => 25,
            Rfq::STATUS_IN_PROGRESS => 25,
            Rfq::STATUS_QUOTED      => 15,
        ];

        foreach ($activeBuyers->take(5) as $buyer) {
            $sellersForThisBuyer = $activeSellers->shuffle()->take(rand(2, 4));

            foreach ($sellersForThisBuyer as $seller) {
                $rfqCount = rand(1, 3);

                for ($i = 0; $i < $rfqCount; $i++) {
                    $product = $activeProducts->where('seller_id', $seller->id)->random();
                    if (! $product) {
                        $product = $activeProducts->random();
                    }

                    $status = $this->getWeightedRandomStatus($statusDistribution);
                    $quantity = rand(1, 100);

                    $createdAt = Carbon::now()->subDays(rand(1, 90))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
                    $updatedAt = $createdAt->copy()->addDays(rand(0, 30))->addHours(rand(0, 23));

                    Rfq::create([
                        'buyer_id'           => $buyer->id,
                        'seller_id'          => $seller->id,
                        'initial_product_id' => $product->id,
                        'initial_quantity'   => $quantity,
                        'shipping_country'   => fake()->randomElement(['USA', 'Canada', 'UK', 'Germany', 'France', 'Australia']),
                        'shipping_address'   => fake()->optional(0.7)->address(),
                        'buyer_message'      => fake()->optional(0.6)->paragraph(2),
                        'status'             => $status,
                        'created_at'         => $createdAt,
                        'updated_at'         => $updatedAt,
                    ]);
                }
            }
        }

        for ($i = 0; $i < 10; $i++) {
            $buyer = $activeBuyers->random();
            $seller = $activeSellers->random();
            $product = $activeProducts->random();
            $status = $this->getWeightedRandomStatus($statusDistribution);

            $createdAt = Carbon::now()->subDays(rand(1, 180))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
            $updatedAt = $createdAt->copy()->addDays(rand(0, 60))->addHours(rand(0, 23));

            Rfq::create([
                'buyer_id'           => $buyer->id,
                'seller_id'          => $seller->id,
                'initial_product_id' => $product->id,
                'initial_quantity'   => rand(1, 200),
                'shipping_country'   => fake()->randomElement(['USA', 'Canada', 'UK', 'Germany', 'France', 'Australia', 'Japan', 'South Korea']),
                'shipping_address'   => fake()->optional(0.8)->address(),
                'buyer_message'      => fake()->optional(0.5)->sentences(rand(1, 3), true),
                'status'             => $status,
                'created_at'         => $createdAt,
                'updated_at'         => $updatedAt,
            ]);
        }
    }

    /**
     * Get a weighted random status based on probability weights
     */
    private function getWeightedRandomStatus(array $statusWeights): string
    {
        $totalWeight = array_sum($statusWeights);
        $randomWeight = rand(1, $totalWeight);
        $currentWeight = 0;

        foreach ($statusWeights as $status => $weight) {
            $currentWeight += $weight;
            if ($randomWeight <= $currentWeight) {
                return $status;
            }
        }

        return array_key_first($statusWeights);
    }
}
