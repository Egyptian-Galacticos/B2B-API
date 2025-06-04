<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'metadata'   => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Disable updated_at since audit logs shouldn't be modified.
     */
    const UPDATED_AT = null;

    /**
     * Get the user that performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted action name.
     */
    public function getFormattedActionAttribute(): string
    {
        return str_replace(['_', '.'], ' ', $this->action);
    }

    /**
     * Get the entity that was acted upon (polymorphic).
     */
    public function entity()
    {
        return match ($this->entity_type) {
            'user'         => $this->belongsTo(User::class, 'entity_id'),
            'company'      => $this->belongsTo(Company::class, 'entity_id'),
            'kyc_document' => $this->belongsTo(KycDocument::class, 'entity_id'),
            'notification' => $this->belongsTo(Notification::class, 'entity_id'),
            default        => null,
        };
    }

    /**
     * Check if the action was performed by an authenticated user.
     */
    public function hasUser(): bool
    {
        return ! is_null($this->user_id);
    }

    /**
     * Get user's display name or 'Anonymous'.
     */
    public function getUserDisplayAttribute(): string
    {
        return $this->user ? $this->user->full_name : 'Anonymous';
    }

    /**
     * Scope for searching logs.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('action', 'like', "%{$search}%")
                ->orWhere('entity_type', 'like', "%{$search}%")
                ->orWhere('ip_address', 'like', "%{$search}%")
                ->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('email', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
        });
    }

    /**
     * Scope for filtering by action.
     */
    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for filtering by entity type.
     */
    public function scopeByEntityType(Builder $query, string $entityType): Builder
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope for filtering by date range.
     */
    public function scopeByDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for authentication related actions.
     */
    public function scopeAuthActions(Builder $query): Builder
    {
        return $query->where('action', 'like', 'auth.%');
    }

    /**
     * Scope to get logs from today.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope to get logs from this week.
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }
}
