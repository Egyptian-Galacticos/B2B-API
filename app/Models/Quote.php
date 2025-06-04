<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    /** @use HasFactory<\Database\Factories\QuoteFactory> */
    use HasFactory;
    protected $fillable = [
        'quote_number',
        'buyer_id',
        'seller_id',
        'status',
        'total_amount',
        'currency',
        'valid_until',
        'terms',
        'metadata',
    ];
    protected $casts = [
        'valid_until'  => 'datetime',
        'total_amount' => 'decimal:2',
        'metadata'     => 'array',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }
}
