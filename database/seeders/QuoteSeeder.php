<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Rfq;
use Illuminate\Database\Seeder;

class QuoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rfqsWithQuotes = Rfq::whereIn('status', [Rfq::STATUS_IN_PROGRESS, Rfq::STATUS_QUOTED])->get();

        if ($rfqsWithQuotes->isEmpty()) {
            return;
        }

        foreach ($rfqsWithQuotes as $rfq) {
            if (fake()->boolean(70)) {
                $createdAt = fake()->dateTimeBetween($rfq->created_at, 'now');
                $updatedAt = fake()->dateTimeBetween($createdAt, 'now');

                $quote = Quote::create([
                    'rfq_id'         => $rfq->id,
                    'total_price'    => 0,
                    'seller_message' => fake()->optional(0.8)->paragraph(2),
                    'status'         => $this->getQuoteStatus($rfq->status),
                    'created_at'     => $createdAt,
                    'updated_at'     => $updatedAt,
                ]);

                $this->createQuoteItems($quote, $rfq);

                $totalPrice = $quote->items->sum(function ($item) {
                    return $item->quantity * $item->unit_price;
                });
                $quote->update(['total_price' => $totalPrice]);

                if ($rfq->status === Rfq::STATUS_QUOTED && $quote->status === Quote::STATUS_PENDING) {
                    $quote->update(['status' => Quote::STATUS_SENT]);
                }
            }
        }
    }

    private function getQuoteStatus(string $rfqStatus): string
    {
        if ($rfqStatus === Rfq::STATUS_QUOTED) {
            return fake()->randomElement([
                Quote::STATUS_SENT,
                Quote::STATUS_SENT,
                Quote::STATUS_ACCEPTED,
                Quote::STATUS_ACCEPTED,
                Quote::STATUS_REJECTED,
                Quote::STATUS_REJECTED,
            ]);
        }

        return fake()->randomElement([
            Quote::STATUS_PENDING,
            Quote::STATUS_PENDING,
            Quote::STATUS_PENDING,
            Quote::STATUS_SENT,
        ]);
    }

    private function createQuoteItems(Quote $quote, Rfq $rfq): void
    {
        $seller = $rfq->seller;
        $sellerProducts = Product::where('seller_id', $seller->id)
            ->where('is_active', true)
            ->get();

        if ($sellerProducts->isEmpty()) {
            $sellerProducts = Product::where('is_active', true)->limit(5)->get();
        }

        $initialProduct = $rfq->initialProduct;
        $basePrice = $initialProduct->price ?? rand(10, 500);

        $quantity = $rfq->initial_quantity;
        $unitPrice = $this->calculateQuotePrice($basePrice);

        QuoteItem::create([
            'quote_id'   => $quote->id,
            'product_id' => $initialProduct->id,
            'quantity'   => $quantity,
            'unit_price' => $unitPrice,
            'notes'      => fake()->optional(0.3)->sentence(),
        ]);

        if (fake()->boolean(50)) {
            $additionalItemsCount = rand(1, 3);
            $additionalProducts = $sellerProducts->random(min($additionalItemsCount, $sellerProducts->count()));

            foreach ($additionalProducts as $product) {
                if ($product->id !== $initialProduct->id) {
                    $quantity = rand(1, 50);
                    $basePrice = $product->price ?? rand(10, 300);
                    $unitPrice = $this->calculateQuotePrice($basePrice);

                    QuoteItem::create([
                        'quote_id'   => $quote->id,
                        'product_id' => $product->id,
                        'quantity'   => $quantity,
                        'unit_price' => $unitPrice,
                        'notes'      => fake()->optional(0.2)->sentence(),
                    ]);
                }
            }
        }
    }

    private function calculateQuotePrice(float $basePrice): float
    {
        $variation = fake()->randomFloat(2, -0.15, 0.15);
        $adjustedPrice = $basePrice * (1 + $variation);

        return round(max($adjustedPrice, 1), 2);
    }
}
