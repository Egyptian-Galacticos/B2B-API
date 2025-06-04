<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    /** @use HasFactory<\Database\Factories\QuoteItemFactory> */
    use HasFactory;
    public $timestamps = false; // No automatic timestamps
    protected $fillable = [
        'quote_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'specifications',
    ];
    protected $casts = [
        'unit_price'  => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
