<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_PENDING_PAYMENT = 'pending_payment';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_DELIVERED_AND_PAID = 'delivered_and_paid';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_VERIFY_SHIPMENT_URL = 'verify_shipment_url';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PENDING_PAYMENT_CONFIRMATION = 'pending_payment_confirmation';
    const BUYER_PAYMENT_REJECTED = 'buyer_payment_rejected';
    const VALID_STATUSES = [
        self::STATUS_PENDING_APPROVAL,
        self::STATUS_APPROVED,
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_PENDING_PAYMENT_CONFIRMATION,
        self::BUYER_PAYMENT_REJECTED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_VERIFY_SHIPMENT_URL,
        self::STATUS_SHIPPED,
        self::STATUS_DELIVERED,
        self::STATUS_DELIVERED_AND_PAID,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];
    protected $fillable = [
        'quote_id',
        'contract_number',
        'buyer_id',
        'seller_id',
        'status',
        'total_amount',
        'currency',
        'contract_date',
        'estimated_delivery',
        'shipping_address',
        'billing_address',
        'terms_and_conditions',
        'metadata',
        'buyer_transaction_id',
        'seller_transaction_id',
        'shipment_url',
    ];
    protected $casts = [
        'contract_date'      => 'datetime',
        'estimated_delivery' => 'datetime',
        'shipping_address'   => 'array',
        'billing_address'    => 'array',
        'metadata'           => 'array',
        'total_amount'       => 'decimal:2',
    ];

    // relationships
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id')->withTrashed();
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id')->withTrashed();
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class);
    }

    // scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('buyer_id', $userId)->orWhere('seller_id', $userId);
    }

    public function scopeForBuyer($query, $userId)
    {
        return $query->where('buyer_id', $userId);
    }

    public function scopeForSeller($query, $userId)
    {
        return $query->where('seller_id', $userId);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_PENDING_APPROVAL);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePendingPayment($query)
    {
        return $query->where('status', self::STATUS_PENDING_PAYMENT);
    }

    public function scopePendingPaymentConfirmation($query)
    {
        return $query->where('status', self::STATUS_PENDING_PAYMENT_CONFIRMATION);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeShipped($query)
    {
        return $query->where('status', self::STATUS_SHIPPED);
    }

    public function scopeVerifyShipmentUrl($query)
    {
        return $query->where('status', self::STATUS_VERIFY_SHIPMENT_URL);
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    public function scopeDeliveredAndPaid($query)
    {
        return $query->where('status', self::STATUS_DELIVERED_AND_PAID);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function canTransitionTo(string $status): bool
    {
        if (! in_array($status, self::VALID_STATUSES)) {
            return false;
        }

        $allowedTransitions = [
            self::STATUS_PENDING_APPROVAL             => [self::STATUS_APPROVED, self::STATUS_PENDING_PAYMENT, self::STATUS_CANCELLED],
            self::STATUS_APPROVED                     => [self::STATUS_PENDING_PAYMENT, self::STATUS_CANCELLED],
            self::STATUS_PENDING_PAYMENT              => [self::STATUS_PENDING_PAYMENT_CONFIRMATION, self::STATUS_CANCELLED],
            self::STATUS_PENDING_PAYMENT_CONFIRMATION => [self::STATUS_IN_PROGRESS, self::BUYER_PAYMENT_REJECTED, self::STATUS_CANCELLED],
            self::BUYER_PAYMENT_REJECTED              => [self::STATUS_PENDING_PAYMENT_CONFIRMATION, self::STATUS_CANCELLED],
            self::STATUS_IN_PROGRESS                  => [self::STATUS_VERIFY_SHIPMENT_URL, self::STATUS_CANCELLED],
            self::STATUS_VERIFY_SHIPMENT_URL          => [self::STATUS_SHIPPED, self::STATUS_CANCELLED],
            self::STATUS_SHIPPED                      => [self::STATUS_DELIVERED, self::STATUS_CANCELLED],
            self::STATUS_DELIVERED                    => [self::STATUS_DELIVERED_AND_PAID, self::STATUS_CANCELLED],
            self::STATUS_DELIVERED_AND_PAID           => [self::STATUS_COMPLETED],
            self::STATUS_COMPLETED                    => [],
            self::STATUS_CANCELLED                    => [],
        ];

        return in_array($status, $allowedTransitions[$this->status] ?? []);
    }

    public function updateStatus(string $newStatus): bool
    {
        if ($this->canTransitionTo($newStatus)) {
            $this->update(['status' => $newStatus]);

            return true;
        }

        return false;
    }

    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPendingPayment(): bool
    {
        return $this->status === self::STATUS_PENDING_PAYMENT;
    }

    public function isPendingPaymentConfirmation(): bool
    {
        return $this->status === self::STATUS_PENDING_PAYMENT_CONFIRMATION;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isShipped(): bool
    {
        return $this->status === self::STATUS_SHIPPED;
    }

    public function isVerifyShipmentUrl(): bool
    {
        return $this->status === self::STATUS_VERIFY_SHIPMENT_URL;
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isDeliveredAndPaid(): bool
    {
        return $this->status === self::STATUS_DELIVERED_AND_PAID;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Set the shipment URL and transition from in_progress to verify_shipment_url status
     * This method is called by the seller when they provide a shipment URL
     */
    public function setShipmentUrl(string $shipmentUrl): bool
    {
        if ($this->status === self::STATUS_IN_PROGRESS && $this->canTransitionTo(self::STATUS_VERIFY_SHIPMENT_URL)) {
            $this->update([
                'shipment_url' => $shipmentUrl,
                'status'       => self::STATUS_VERIFY_SHIPMENT_URL,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Verify shipment URL by admin (transition from verify_shipment_url to shipped)
     * This method is called by the admin when they verify the shipment URL
     */
    public function verifyShipmentUrlByAdmin(): bool
    {
        if ($this->status === self::STATUS_VERIFY_SHIPMENT_URL && $this->canTransitionTo(self::STATUS_SHIPPED)) {
            $this->update(['status' => self::STATUS_SHIPPED]);

            return true;
        }

        return false;
    }

    /**
     * Mark as delivered by buyer (transition from shipped to delivered)
     * This method is called by the buyer when they confirm delivery
     */
    public function confirmDeliveryByBuyer(): bool
    {
        if ($this->status === self::STATUS_SHIPPED && $this->canTransitionTo(self::STATUS_DELIVERED)) {
            $this->update(['status' => self::STATUS_DELIVERED]);

            return true;
        }

        return false;
    }

    /**
     * Check if contract is waiting for shipment URL verification
     */
    public function isWaitingForShipmentUrlVerification(): bool
    {
        return $this->status === self::STATUS_VERIFY_SHIPMENT_URL;
    }

    /**
     * Confirm payment after delivery (transition from delivered to delivered_and_paid)
     * This method is called when payment is confirmed after delivery
     */
    public function confirmPaymentAfterDelivery(): bool
    {
        if ($this->status === self::STATUS_DELIVERED && $this->canTransitionTo(self::STATUS_DELIVERED_AND_PAID)) {
            $this->update(['status' => self::STATUS_DELIVERED_AND_PAID]);

            return true;
        }

        return false;
    }

    /**
     * Complete the contract (transition from delivered_and_paid to completed)
     * This method is called to finalize the contract
     */
    public function completeContract(): bool
    {
        if ($this->status === self::STATUS_DELIVERED_AND_PAID && $this->canTransitionTo(self::STATUS_COMPLETED)) {
            $this->update(['status' => self::STATUS_COMPLETED]);

            return true;
        }

        return false;
    }
}
