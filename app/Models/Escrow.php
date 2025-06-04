<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Escrow extends Model
{
    /** @use HasFactory<\Database\Factories\EscrowFactory> */
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'contract_id',
        'status',
        'amount',
        'currency',
    ];
    protected $casts = [
        'amount'     => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function contract()
    {
        // return $this->belongsTo(Contract::class);
    }
}
