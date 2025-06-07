<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailVerificationToken extends Model
{
    protected $fillable = [
        'token',
        'email',
        'expires_at',
        'verifiable_type',
        'verifiable_id',
    ];
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function verifiable()
    {
        return $this->morphTo();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
