<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    const STATUS_ACTIVE = 'active';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_DISPUTED = 'disputed';
    const VALID_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_DISPUTED,
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

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeDisputed($query)
    {
        return $query->where('status', self::STATUS_DISPUTED);
    }

    public function canTransitionTo(string $status): bool
    {
        if (! in_array($status, self::VALID_STATUSES)) {
            return false;
        }

        $allowedTransitions = [
            self::STATUS_ACTIVE      => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED, self::STATUS_DISPUTED],
            self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED, self::STATUS_CANCELLED, self::STATUS_DISPUTED],
            self::STATUS_DISPUTED    => [self::STATUS_ACTIVE, self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
            self::STATUS_COMPLETED   => [],
            self::STATUS_CANCELLED   => [],
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

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isDisputed(): bool
    {
        return $this->status === self::STATUS_DISPUTED;
    }
}
