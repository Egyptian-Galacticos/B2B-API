<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    /** @use HasFactory<\Database\Factories\QuoteFactory> */
    use HasFactory, SoftDeletes;
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const VALID_STATUSES = [
        self::STATUS_PENDING,
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
    ];
    protected $casts = [
        'total_price' => 'decimal:2',
        'status'      => 'string',
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

    public function seller()
    {
        // Return direct seller if exists, otherwise get from RFQ
        return $this->seller_id ? $this->directSeller : ($this->rfq ? $this->rfq->seller() : null);
    }

    public function buyer()
    {
        // Return direct buyer if exists, otherwise get from RFQ
        return $this->buyer_id ? $this->directBuyer : ($this->rfq ? $this->rfq->buyer() : null);
    }

    // scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

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

    // accessors
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
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

    // Status transition helpers
    public function canTransitionTo($newStatus)
    {
        if (! in_array($newStatus, self::VALID_STATUSES)) {
            return false;
        }

        $validTransitions = [
            self::STATUS_PENDING  => [self::STATUS_SENT],
            self::STATUS_SENT     => [self::STATUS_ACCEPTED, self::STATUS_REJECTED],
            self::STATUS_ACCEPTED => [],
            self::STATUS_REJECTED => [],
        ];

        return in_array($newStatus, $validTransitions[$this->status] ?? []);
    }

    public function transitionTo($newStatus)
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException("Cannot transition from {$this->status} to {$newStatus}");
        }

        // Only update RFQ status if there's an RFQ
        if ($newStatus === self::STATUS_SENT && $this->rfq && $this->rfq->status !== Rfq::STATUS_QUOTED) {
            $this->rfq->transitionTo(Rfq::STATUS_QUOTED);
        }

        // Note: RFQ remains in 'Quoted' status as it's the final status in the new workflow

        $this->status = $newStatus;

        return $this->save();
    }
}
