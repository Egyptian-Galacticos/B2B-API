<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Quote;
use Exception;
use Illuminate\Database\Seeder;

class ContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $acceptedQuotes = Quote::where('status', Quote::STATUS_ACCEPTED)
            ->doesntHave('contract')
            ->with(['items.product', 'rfq.buyer.company', 'directBuyer.company'])
            ->get();

        if ($acceptedQuotes->isEmpty()) {
            return;
        }

        foreach ($acceptedQuotes as $quote) {

            $buyerId = $quote->buyer_id;
            $sellerId = $quote->seller_id;

            if (! $buyerId && $quote->rfq) {
                $buyerId = $quote->rfq->buyer_id;
            }
            if (! $sellerId && $quote->rfq) {
                $sellerId = $quote->rfq->seller_id;
            }

            if (! $buyerId || ! $sellerId) {
                continue;
            }

            $this->createContractFromQuote($quote);
        }
    }

    private function createContractFromQuote(Quote $quote): Contract
    {
        $buyer = $quote->buyer_id ? $quote->directBuyer : ($quote->rfq ? $quote->rfq->buyer : null);

        if (! $buyer) {
            throw new Exception("No buyer found for quote {$quote->id}");
        }

        $buyerId = $quote->buyer_id ?: $buyer->id;

        $sellerId = $quote->seller_id ?: ($quote->rfq ? $quote->rfq->seller_id : null);

        if (! $sellerId) {
            throw new Exception("No seller found for quote {$quote->id}");
        }

        $year = $quote->created_at->year;
        $existingContractsCount = Contract::whereYear('created_at', $year)->count();
        $contractNumber = "CON-{$year}-".str_pad($existingContractsCount + 1, 6, '0', STR_PAD_LEFT);

        $contractDate = $quote->accepted_at ?? $quote->updated_at;
        $estimatedDelivery = $contractDate->copy()->addDays(fake()->numberBetween(7, 45));

        $contract = Contract::create([
            'quote_id'             => $quote->id,
            'contract_number'      => $contractNumber,
            'buyer_id'             => $buyerId,
            'seller_id'            => $sellerId,
            'status'               => $this->getContractStatus(),
            'total_amount'         => $quote->total_price,
            'currency'             => 'USD',
            'contract_date'        => $contractDate,
            'estimated_delivery'   => $estimatedDelivery,
            'shipping_address'     => $this->generateShippingAddress($quote),
            'billing_address'      => $this->getBillingAddress($buyer),
            'terms_and_conditions' => $this->generateTermsAndConditions(),
            'created_at'           => $contractDate,
            'updated_at'           => fake()->dateTimeBetween($contractDate, 'now'),
        ]);

        foreach ($quote->items as $quoteItem) {
            $totalPrice = $quoteItem->quantity * $quoteItem->unit_price;

            $contract->items()->create([
                'product_id'     => $quoteItem->product_id,
                'quantity'       => $quoteItem->quantity,
                'unit_price'     => $quoteItem->unit_price,
                'total_price'    => $totalPrice,
                'specifications' => $this->generateSpecifications($quoteItem),
            ]);
        }

        return $contract;
    }

    private function getContractStatus(): string
    {
        return fake()->randomElement([
            Contract::STATUS_ACTIVE,
            Contract::STATUS_ACTIVE,
            Contract::STATUS_IN_PROGRESS,
            Contract::STATUS_IN_PROGRESS,
            Contract::STATUS_COMPLETED,
            Contract::STATUS_CANCELLED,
        ]);
    }

    private function getBillingAddress($buyer): string
    {
        if ($buyer && $buyer->company && $buyer->company->address) {
            $address = $buyer->company->address;
            if (is_array($address)) {
                return implode(', ', array_filter($address));
            }

            return $address;
        }

        return fake()->address();
    }

    private function generateShippingAddress(Quote $quote): string
    {
        $buyer = $quote->buyer_id ? $quote->directBuyer : ($quote->rfq ? $quote->rfq->buyer : null);

        if ($buyer && $buyer->company && $buyer->company->address) {
            $address = $buyer->company->address;
            if (is_array($address)) {
                return implode(', ', array_filter($address));
            }

            return $address;
        }

        return fake()->streetAddress().', '.
            fake()->city().', '.
            fake()->stateAbbr().' '.
            fake()->postcode().', '.
            fake()->country();
    }

    private function generateTermsAndConditions(): string
    {
        $terms = [
            'Payment due within 30 days of delivery.',
            'Goods remain property of seller until full payment received.',
            'Delivery charges are excluded unless otherwise stated.',
            'Returns accepted within 14 days in original condition.',
            'Force majeure events may delay delivery without penalty.',
            'Any disputes shall be resolved through arbitration.',
            'This contract is governed by local commercial law.',
        ];

        return implode(' ', fake()->randomElements($terms, fake()->numberBetween(3, 5)));
    }

    private function generateSpecifications($quoteItem): ?string
    {
        if (! $quoteItem->product) {
            return null;
        }

        $specs = [];

        if (fake()->boolean(70)) {
            $specs[] = 'Color: '.fake()->colorName();
        }

        if (fake()->boolean(60)) {
            $specs[] = 'Material: '.fake()->randomElement(['Steel', 'Aluminum', 'Plastic', 'Wood', 'Composite']);
        }

        if (fake()->boolean(50)) {
            $specs[] = 'Warranty: '.fake()->randomElement(['1 year', '2 years', '6 months', '90 days']);
        }

        return empty($specs) ? null : implode(', ', $specs);
    }
}
