<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

class Quote extends Model
{
    /** @use HasFactory<\Database\Factories\QuoteFactory> */
    use HasFactory, SoftDeletes;
    const STATUS_SENT = 'sent';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const VALID_STATUSES = [
        self::STATUS_SENT,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
    ];
    protected $fillable = [
        'rfq_id',
        'conversation_id',
        'seller_id',
        'buyer_id',
        'total_price',
        'seller_message',
        'status',
        'accepted_at',
    ];
    protected $casts = [
        'total_price' => 'decimal:2',
        'status'      => 'string',
        'accepted_at' => 'datetime',
    ];

    // releationships
    public function rfq()
    {
        return $this->belongsTo(Rfq::class)->withDefault();
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function contract()
    {
        return $this->hasOne(Contract::class);
    }

    public function items()
    {
        return $this->hasMany(QuoteItem::class);
    }

    // Direct seller relationship (for chat-based quotes)
    public function directSeller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    // Direct buyer relationship (for chat-based quotes)
    public function directBuyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    // Primary seller relationship - always returns a relationship instance
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    // Primary buyer relationship - always returns a relationship instance
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    // Helper methods to get seller/buyer with fallback to RFQ
    public function getSellerAttribute()
    {
        return $this->seller_id ? $this->directSeller : ($this->rfq ? $this->rfq->seller : null);
    }

    public function getBuyerAttribute()
    {
        return $this->buyer_id ? $this->directBuyer : ($this->rfq ? $this->rfq->buyer : null);
    }

    // scopes
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function canTransitionTo($newStatus)
    {
        if (! in_array($newStatus, self::VALID_STATUSES)) {
            return false;
        }

        $validTransitions = [
            self::STATUS_SENT     => [self::STATUS_ACCEPTED, self::STATUS_REJECTED],
            self::STATUS_ACCEPTED => [],
            self::STATUS_REJECTED => [],
        ];

        return in_array($newStatus, $validTransitions[$this->status] ?? []);
    }

    public function transitionTo($newStatus)
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException("Cannot transition from {$this->status} to {$newStatus}");
        }

        if ($newStatus === self::STATUS_SENT && $this->rfq && $this->rfq->status !== Rfq::STATUS_QUOTED) {
            $this->rfq->transitionTo(Rfq::STATUS_QUOTED);
        }

        $this->status = $newStatus;

        return $this->save();
    }

    public function hasContract(): bool
    {
        return $this->contract()->exists();
    }

    public function acceptAndCreateContract(): Contract
    {
        if ($this->hasContract()) {
            throw new InvalidArgumentException('Contract already exists for this quote');
        }

        $buyerId = $this->buyer_id ?: ($this->rfq ? $this->rfq->buyer_id : null);
        $sellerId = $this->seller_id ?: ($this->rfq ? $this->rfq->seller_id : null);

        if (! $buyerId || ! $sellerId) {
            throw new InvalidArgumentException('Quote must have valid buyer and seller');
        }

        $this->update([
            'status'      => self::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);

        $buyer = User::find($buyerId);
        $seller = User::find($sellerId);

        $billingAddress = $this->generateBillingAddress($buyer);

        $shippingAddress = $this->generateShippingAddress($buyer);

        $estimatedDelivery = $this->generateEstimatedDelivery();

        $termsAndConditions = $this->generateTermsAndConditions();

        $contract = Contract::create([
            'quote_id'             => $this->id,
            'contract_number'      => $this->generateContractNumber(),
            'buyer_id'             => $buyerId,
            'seller_id'            => $sellerId,
            'status'               => Contract::STATUS_ACTIVE,
            'total_amount'         => $this->total_price,
            'currency'             => $this->currency ?? 'USD',
            'contract_date'        => now(),
            'estimated_delivery'   => $estimatedDelivery,
            'shipping_address'     => $shippingAddress,
            'billing_address'      => $billingAddress,
            'terms_and_conditions' => $termsAndConditions,
        ]);

        foreach ($this->items as $quoteItem) {
            $contract->items()->create([
                'product_id'     => $quoteItem->product_id,
                'quantity'       => $quoteItem->quantity,
                'unit_price'     => $quoteItem->unit_price,
                'total_price'    => $quoteItem->total_price,
                'specifications' => $quoteItem->specifications ?? null,
            ]);
        }

        return $contract;
    }

    private function generateContractNumber(): string
    {
        $year = date('Y');

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $count = Contract::whereYear('created_at', $year)->count() + $attempt;
            $contractNumber = "CON-{$year}-".str_pad($count, 6, '0', STR_PAD_LEFT);

            if (! Contract::where('contract_number', $contractNumber)->exists()) {
                return $contractNumber;
            }
        }

        $timestamp = time();

        return "CON-{$year}-".str_pad($timestamp % 1000000, 6, '0', STR_PAD_LEFT);
    }

    private function generateBillingAddress(?User $buyer): ?string
    {
        if (! $buyer || ! $buyer->company || ! $buyer->company->address) {
            return null;
        }

        $address = $buyer->company->address;
        if (is_array($address)) {
            return implode(', ', array_filter([
                $address['street'] ?? '',
                $address['city'] ?? '',
                $address['state'] ?? '',
                $address['country'] ?? '',
                $address['postal_code'] ?? '',
            ]));
        }

        return $address;
    }

    private function generateShippingAddress(?User $buyer): ?string
    {
        if ($this->rfq && $this->rfq->shipping_address) {
            return $this->rfq->shipping_address;
        }

        if ($buyer && $buyer->company && $buyer->company->address) {
            $address = $buyer->company->address;
            if (is_array($address)) {
                return implode(', ', array_filter([
                    $address['street'] ?? '',
                    $address['city'] ?? '',
                    $address['state'] ?? '',
                    $address['country'] ?? '',
                    $address['postal_code'] ?? '',
                ]));
            }

            return $address;
        }

        return null;
    }

    private function generateEstimatedDelivery(): Carbon
    {
        if ($this->rfq) {
            $baseDeliveryDays = 7;
            $quantity = $this->rfq->initial_quantity ?? 1;

            if ($quantity > 100) {
                $baseDeliveryDays += 14;
            } elseif ($quantity > 50) {
                $baseDeliveryDays += 7;
            }

            return now()->addDays($baseDeliveryDays);
        }

        return now()->addDays(14);
    }

    private function generateTermsAndConditions(): string
    {
        $baseTerms = [
            'Payment terms: Net 30 days from invoice date.',
            'Delivery charges may apply and will be quoted separately.',
            'Returns accepted within 30 days in original condition.',
            'Prices are valid for 30 days from quote date.',
            'This contract is governed by applicable commercial law.',
        ];

        if ($this->rfq && $this->rfq->shipping_country) {
            $baseTerms[] = "International shipping to {$this->rfq->shipping_country} - additional duties and taxes may apply.";
        }

        $totalQuantity = $this->items->sum('quantity');
        if ($totalQuantity > 100) {
            $baseTerms[] = 'Large quantity order - delivery may be split into multiple shipments.';
        }

        return implode(' ', $baseTerms);
    }
}
