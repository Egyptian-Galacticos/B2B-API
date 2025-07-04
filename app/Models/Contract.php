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
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const VALID_STATUSES = [
        self::STATUS_PENDING_APPROVAL,
        self::STATUS_APPROVED,
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_IN_PROGRESS,
        self::STATUS_SHIPPED,
        self::STATUS_DELIVERED,
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
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
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

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeShipped($query)
    {
        return $query->where('status', self::STATUS_SHIPPED);
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
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
            self::STATUS_PENDING_APPROVAL => [self::STATUS_APPROVED, self::STATUS_CANCELLED],
            self::STATUS_APPROVED         => [self::STATUS_PENDING_PAYMENT, self::STATUS_CANCELLED],
            self::STATUS_PENDING_PAYMENT  => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
            self::STATUS_IN_PROGRESS      => [self::STATUS_SHIPPED, self::STATUS_CANCELLED],
            self::STATUS_SHIPPED          => [self::STATUS_DELIVERED, self::STATUS_CANCELLED],
            self::STATUS_DELIVERED        => [self::STATUS_COMPLETED],
            self::STATUS_COMPLETED        => [],
            self::STATUS_CANCELLED        => [],
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

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isShipped(): bool
    {
        return $this->status === self::STATUS_SHIPPED;
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}
