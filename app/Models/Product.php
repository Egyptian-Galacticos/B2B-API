<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'sku',
        'name',
        'description',
        'hs_code',
        'price',
        'currency',
        'minimum_order_quantity',
        'lead_time_days',
        'origin',
        'category_id',
        'specifications',
        'certifications',
        'dimensions',
        'is_active',
    ];

    protected $casts = [
        'specifications' => 'array',
        'certifications' => 'array',
        'dimensions' => 'array',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function contractItems(): HasMany
    {
        return $this->hasMany(ContractItem::class);
    }

    public function quoteItems(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }
}
