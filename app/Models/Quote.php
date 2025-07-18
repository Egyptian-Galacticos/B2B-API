<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

class Quote extends Model
{
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

    // for chat-based quotes
    public function directSeller()
    {
        return $this->belongsTo(User::class, 'seller_id')->withTrashed();
    }

    // for chat-based quotes
    public function directBuyer()
    {
        return $this->belongsTo(User::class, 'buyer_id')->withTrashed();
    }

    public function getSellerAttribute()
    {
        return $this->seller_id ? $this->directSeller : ($this->rfq ? $this->rfq->seller : null);
    }

    public function getBuyerAttribute()
    {
        return $this->buyer_id ? $this->directBuyer : ($this->rfq ? $this->rfq->buyer : null);
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

    public function scopeForBuyer($query, $buyerId)
    {
        return $query->where('buyer_id', $buyerId);
    }

    public function scopeForSeller($query, $sellerId)
    {
        return $query->where('seller_id', $sellerId);
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
}
