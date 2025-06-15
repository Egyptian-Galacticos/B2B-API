<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceTier extends Model
{
    protected $fillable = [
        'product_id',
        'from_quantity',
        'to_quantity',
        'price',
        'currency',
    ];
    protected $casts = [
        'from_quantity' => 'decimal:2',
        'to_quantity'   => 'decimal:2',
        'price'         => 'decimal:2',
        'currency'      => 'string',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
