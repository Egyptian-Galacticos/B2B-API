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

    public function items()
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function seller()
    {
        return $this->rfq->seller();
    }

    public function buyer()
    {
        return $this->rfq->buyer();
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

        if ($newStatus === self::STATUS_SENT && $this->rfq && $this->rfq->status !== Rfq::STATUS_QUOTED) {
            $this->rfq->transitionTo(Rfq::STATUS_QUOTED);
        }

        // When a quote is accepted, also accept the RFQ
        if ($newStatus === self::STATUS_ACCEPTED && $this->rfq && $this->rfq->canTransitionTo(Rfq::STATUS_ACCEPTED)) {
            $this->rfq->transitionTo(Rfq::STATUS_ACCEPTED);
        }

        $this->status = $newStatus;

        return $this->save();
    }
}
