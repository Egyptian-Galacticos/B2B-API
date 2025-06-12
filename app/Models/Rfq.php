<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rfq extends Model
{
    /** @use HasFactory<\Database\Factories\RfqFactory> */
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'buyer_id',
        'seller_id',
        'initial_product_id',
        'initial_quantity',
        'shipping_country',
        'shipping_address',
        'buyer_message',
        'status',
    ];
    protected $casts = [
        'initial_quantity' => 'integer',
        'status'           => 'string',
    ];

    // relationships
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function initialProduct()
    {
        return $this->belongsTo(Product::class, 'initial_product_id');
    }

    public function quotes()
    {
        // return $this->hasMany(Quote::class);
    }

    // scope
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeQuoted($query)
    {
        return $query->where('status', 'quoted');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeForBuyer($query, $buyerId)
    {
        return $query->where('buyer_id', $buyerId);
    }

    public function scopeForSeller($query, $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    // accessors
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isQuoted()
    {
        return $this->status === 'quoted';
    }

    public function isAccepted()
    {
        return $this->status === 'accepted';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isClosed()
    {
        return $this->status === 'closed';
    }
}
