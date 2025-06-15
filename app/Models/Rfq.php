<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rfq extends Model
{
    /** @use HasFactory<\Database\Factories\RfqFactory> */
    use HasFactory, SoftDeletes;
    const STATUS_PENDING = 'pending';
    const STATUS_SEEN = 'seen';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_QUOTED = 'quoted';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CLOSED = 'closed';
    const VALID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_SEEN,
        self::STATUS_IN_PROGRESS,
        self::STATUS_QUOTED,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
        self::STATUS_CLOSED,
    ];
    protected $fillable = [
        'buyer_id',
        'seller_id',
        'initial_product_id',
        'initial_quantity',
        'shipping_country',
        'shipping_address',
        'buyer_message',
        'status',
    ];
    protected $casts = [
        'initial_quantity' => 'integer',
        'status'           => 'string',
    ];

    // relationships
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function initialProduct()
    {
        return $this->belongsTo(Product::class, 'initial_product_id');
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    // scope
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSeen($query)
    {
        return $query->where('status', self::STATUS_SEEN);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeQuoted($query)
    {
        return $query->where('status', self::STATUS_QUOTED);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public function scopeForBuyer($query, $buyerId)
    {
        return $query->where('buyer_id', $buyerId);
    }

    public function scopeForSeller($query, $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    // accessors
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSeen()
    {
        return $this->status === self::STATUS_SEEN;
    }

    public function isInProgress()
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isQuoted()
    {
        return $this->status === self::STATUS_QUOTED;
    }

    public function isAccepted()
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isRejected()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isClosed()
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function canTransitionTo($newStatus)
    {
        if (! in_array($newStatus, self::VALID_STATUSES)) {
            return false;
        }

        $validTransitions = [
            self::STATUS_PENDING     => [self::STATUS_SEEN, self::STATUS_REJECTED, self::STATUS_IN_PROGRESS, self::STATUS_QUOTED],
            self::STATUS_SEEN        => [self::STATUS_IN_PROGRESS, self::STATUS_REJECTED, self::STATUS_QUOTED, self::STATUS_ACCEPTED],
            self::STATUS_IN_PROGRESS => [self::STATUS_QUOTED, self::STATUS_REJECTED, self::STATUS_ACCEPTED], // Can accept/reject directly from in_progress
            self::STATUS_QUOTED      => [self::STATUS_ACCEPTED, self::STATUS_REJECTED],
            self::STATUS_ACCEPTED    => [self::STATUS_CLOSED],
            self::STATUS_REJECTED    => [],
            self::STATUS_CLOSED      => [],
        ];

        return in_array($newStatus, $validTransitions[$this->status] ?? []);
    }

    public function transitionTo($newStatus)
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException("Cannot transition from {$this->status} to {$newStatus}");
        }

        $this->status = $newStatus;

        if ($newStatus === self::STATUS_ACCEPTED) {
            $this->quotes()->where('status', 'sent')->update(['status' => 'accepted']);
        }

        if ($newStatus === self::STATUS_REJECTED) {
            $this->quotes()->where('status', 'sent')->update(['status' => 'rejected']);
        }

        return $this->save();
    }
}
