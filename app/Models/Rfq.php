<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rfq extends Model
{
    /** @use HasFactory<\Database\Factories\RfqFactory> */
    use HasFactory, SoftDeletes;
    const STATUS_PENDING = 'Pending';
    const STATUS_SEEN = 'Seen';
    const STATUS_IN_PROGRESS = 'In Progress';
    const STATUS_QUOTED = 'Quoted';
    const STATUS_REJECTED = 'Rejected';
    const VALID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_SEEN,
        self::STATUS_IN_PROGRESS,
        self::STATUS_QUOTED,
        self::STATUS_REJECTED,
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

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
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

    public function isRejected()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function canTransitionTo($newStatus)
    {
        if (! in_array($newStatus, self::VALID_STATUSES)) {
            return false;
        }

        $validTransitions = [
            self::STATUS_PENDING     => [self::STATUS_SEEN, self::STATUS_IN_PROGRESS, self::STATUS_QUOTED, self::STATUS_REJECTED],
            self::STATUS_SEEN        => [self::STATUS_IN_PROGRESS, self::STATUS_QUOTED, self::STATUS_REJECTED],
            self::STATUS_IN_PROGRESS => [self::STATUS_QUOTED],
            self::STATUS_QUOTED      => [],
            self::STATUS_REJECTED    => [],
        ];

        return in_array($newStatus, $validTransitions[$this->status] ?? []);
    }

    public function transitionTo($newStatus)
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException("Cannot transition from {$this->status} to {$newStatus}");
        }

        $this->status = $newStatus;

        return $this->save();
    }
}
