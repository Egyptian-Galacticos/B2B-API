<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    /** @use HasFactory<\Database\Factories\QuoteFactory> */
    use HasFactory, SoftDeletes;
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
        // return $this->hasMany(QuoteItem::class);
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
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeSeen($query)
    {
        return $query->where('status', 'seen');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // accessors
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isSeen(): bool
    {
        return $this->status === 'seen';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
