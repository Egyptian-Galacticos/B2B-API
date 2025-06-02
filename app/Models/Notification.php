<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_read' => 'boolean',
            'created_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    /**
     * Disable updated_at since notifications don't need to be updated.
     */
    const UPDATED_AT = null;

    /**
     * Get the user that owns the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get notification type display name.
     */
    public function getTypeDisplayAttribute(): array
    {
        return match ($this->type) {
            'email' => ['text' => 'Email', 'icon' => 'envelope', 'class' => 'primary'],
            'sms' => ['text' => 'SMS', 'icon' => 'mobile', 'class' => 'success'],
            'push' => ['text' => 'Push', 'icon' => 'bell', 'class' => 'warning'],
            'in_app' => ['text' => 'In-App', 'icon' => 'computer', 'class' => 'info'],
            'system' => ['text' => 'System', 'icon' => 'gear', 'class' => 'secondary'],
            default => ['text' => 'Unknown', 'icon' => 'question', 'class' => 'dark'],
        };
    }

    /**
     * Get formatted time ago.
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get notification priority from data.
     */
    public function getPriorityAttribute(): string
    {
        return $this->data['priority'] ?? 'normal';
    }

    /**
     * Check if notification is unread.
     */
    public function isUnread(): bool
    {
        return ! $this->is_read;
    }

    /**
     * Check if notification is read.
     */
    public function isRead(): bool
    {
        return $this->is_read;
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return true;
        }

        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark notification as unread.
     */
    public function markAsUnread(): bool
    {
        return $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Get action URL from data.
     */
    public function getActionUrlAttribute(): ?string
    {
        return $this->data['action_url'] ?? null;
    }

    /**
     * Get action text from data.
     */
    public function getActionTextAttribute(): ?string
    {
        return $this->data['action_text'] ?? null;
    }

    /**
     * Check if notification has an action.
     */
    public function hasAction(): bool
    {
        return ! empty($this->action_url);
    }

    /**
     * Scope to get unread notifications.
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to get read notifications.
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope to get notifications by type.
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get high priority notifications.
     */
    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereJsonContains('data->priority', 'high');
    }

    /**
     * Scope to get recent notifications.
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get today's notifications.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Create a new notification for a user.
     */
    public static function createForUser(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): self {
        return self::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Create a system notification for a user.
     */
    public static function createSystemNotification(
        User $user,
        string $title,
        string $message,
        array $data = []
    ): self {
        return self::createForUser($user, 'system', $title, $message, $data);
    }

    /**
     * Mark all notifications as read for a user.
     */
    public static function markAllAsReadForUser(User $user): int
    {
        return self::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
}
