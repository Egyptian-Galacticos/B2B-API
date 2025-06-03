<?php

namespace App\Models;

use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use HasFactory;

    protected $fillable = [
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
        'contract_date' => 'datetime',
        'estimated_delivery' => 'datetime',
        'metadata' => 'array',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the customer that owns the contract.
     */
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
        return $this->hasMany(ContractItem::class);
    }
}
