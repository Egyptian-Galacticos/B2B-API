<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    /** @use HasFactory<\Database\Factories\QuoteFactory> */
    use HasFactory, SoftDeletes;

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';

    // All valid statuses
    const VALID_STATUSES = [
        self::STATUS_DRAFT,
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
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
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
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
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

        // Define valid transitions
        $validTransitions = [
            self::STATUS_DRAFT    => [self::STATUS_SENT],
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

        // When quote is sent, update RFQ status to 'quoted'
        if ($newStatus === self::STATUS_SENT && $this->rfq) {
            $this->rfq->transitionTo(Rfq::STATUS_QUOTED);
        }

        // When quote is accepted, update RFQ status to 'accepted'
        if ($newStatus === self::STATUS_ACCEPTED && $this->rfq) {
            $this->rfq->transitionTo(Rfq::STATUS_ACCEPTED);
        }

        // When quote is rejected, update RFQ status to 'rejected'
        if ($newStatus === self::STATUS_REJECTED && $this->rfq) {
            $this->rfq->transitionTo(Rfq::STATUS_REJECTED);
        }

        $this->status = $newStatus;

        return $this->save();
    }
}
