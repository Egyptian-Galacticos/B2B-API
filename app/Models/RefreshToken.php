<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RefreshToken extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'revoked',
    ];
    protected $casts = [
        'expires_at' => 'datetime',
        'revoked'    => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($token) {
            $token->token = Str::random(64);
            $token->expires_at = now()->addDays(30);
            $token->revoked = false;
        });
    }

    /**
     * Check if the refresh token is active (not expired and not revoked)
     */
    public function isActive(): bool
    {
        return ! $this->revoked && $this->expires_at->isFuture();
    }

    /**
     * Check if the refresh token is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Revoke the refresh token
     */
    public function revoke(): bool
    {
        return $this->update(['revoked' => true]);
    }

    /**
     * Get the user that owns the refresh token
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
