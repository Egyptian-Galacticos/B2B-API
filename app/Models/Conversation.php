<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    /** @use HasFactory<\Database\Factories\ConversationFactory> */
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'type',
        'seller_id',
        'buyer_id',
        'title',
        'last_message_id',
        'last_activity_at',
        'is_active',
    ];
    protected $casts = [
        'last_activity_at' => 'datetime',
        'is_active'        => 'boolean',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
        'deleted_at'       => 'datetime',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function participants()
    {
        return $this->seller()->union($this->buyer());
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage()
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    // Helper methods
    public function isParticipant($userId)
    {
        return $this->seller_id == $userId || $this->buyer_id == $userId;
    }

    public function getOtherParticipant($userId)
    {
        if ($this->seller_id == $userId) {
            return $this->buyer;
        } elseif ($this->buyer_id == $userId) {
            return $this->seller;
        }

        return null;
    }

    public function updateLastActivity()
    {
        $this->update(['last_activity_at' => now()]);
    }
}
