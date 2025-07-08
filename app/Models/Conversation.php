<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;
    protected $fillable = [
        'seller_id',
        'buyer_id',
        'type',
        'title',
        'last_message_id',
        'last_activity_at',
        'is_active',
    ];
    protected $casts = [
        'last_activity_at' => 'datetime',
        'is_active'        => 'boolean',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    public function participants()
    {
        return collect([$this->seller, $this->buyer])->filter();
    }

    public function isParticipant(int $userId): bool
    {
        return $this->seller_id === $userId || $this->buyer_id === $userId;
    }

    public function getOtherParticipant(int $userId)
    {
        if ($this->seller_id === $userId) {
            return $this->buyer;
        }

        if ($this->buyer_id === $userId) {
            return $this->seller;
        }

        return null;
    }
}
