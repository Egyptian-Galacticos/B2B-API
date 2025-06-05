<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
    protected $fillable = ['user_id'];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('revoked', false)->where('expires_at', '>', now());
    }

    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if ($model->user_id) {
                static::where('user_id', $model->user_id)
                    ->delete();
            }
            if (! $model->user_id) {
                throw new \InvalidArgumentException('User ID is required for creating a refresh token.');
            }
            $model->token = bin2hex(random_bytes(32));
            $model->expires_at = now()->addDays(30);
            $model->revoked = false;
        });
    }
}
