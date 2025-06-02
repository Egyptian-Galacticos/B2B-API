<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class KycDocument extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'type',
        'document_url',
        'status',
        'rejection_reason',
        'reviewer_id',
        'submitted_at',
        'reviewed_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Get the user who submitted the document.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reviewer who reviewed the document.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get document type display name.
     */
    public function getTypeDisplayAttribute(): string
    {
        return match ($this->type) {
            'passport' => 'Passport',
            'national_id' => 'National ID',
            'drivers_license' => 'Driver\'s License',
            'utility_bill' => 'Utility Bill',
            'bank_statement' => 'Bank Statement',
            'other' => 'Other Document',
            default => 'Unknown Document',
        };
    }

    /**
     * Get status display name with color class.
     */
    public function getStatusDisplayAttribute(): array
    {
        return match ($this->status) {
            'pending' => ['text' => 'Pending Review', 'class' => 'warning'],
            'approved' => ['text' => 'Approved', 'class' => 'success'],
            'rejected' => ['text' => 'Rejected', 'class' => 'danger'],
            'under_review' => ['text' => 'Under Review', 'class' => 'info'],
            default => ['text' => 'Unknown', 'class' => 'secondary'],
        };
    }

    /**
     * Get the full URL for the document.
     */
    public function getDocumentFullUrlAttribute(): string
    {
        if (str_starts_with($this->document_url, 'http')) {
            return $this->document_url;
        }

        return Storage::url($this->document_url);
    }

    /**
     * Check if document is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if document is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if document is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if document is under review.
     */
    public function isUnderReview(): bool
    {
        return $this->status === 'under_review';
    }

    /**
     * Check if document needs review.
     */
    public function needsReview(): bool
    {
        return in_array($this->status, ['pending', 'under_review']);
    }

    /**
     * Approve the document.
     */
    public function approve(int $reviewerId): bool
    {
        return $this->update([
            'status' => 'approved',
            'reviewer_id' => $reviewerId,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Reject the document.
     */
    public function reject(int $reviewerId, string $reason): bool
    {
        return $this->update([
            'status' => 'rejected',
            'reviewer_id' => $reviewerId,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Mark as under review.
     */
    public function markUnderReview(int $reviewerId): bool
    {
        return $this->update([
            'status' => 'under_review',
            'reviewer_id' => $reviewerId,
        ]);
    }

    /**
     * Scope to get pending documents.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get approved documents.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get rejected documents.
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope to get documents under review.
     */
    public function scopeUnderReview(Builder $query): Builder
    {
        return $query->where('status', 'under_review');
    }

    /**
     * Scope to get documents by type.
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get documents needing review.
     */
    public function scopeNeedsReview(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'under_review']);
    }

    /**
     * Scope to get recently submitted documents.
     */
    public function scopeRecentlySubmitted(Builder $query, int $days = 7): Builder
    {
        return $query->where('submitted_at', '>=', now()->subDays($days));
    }

    /**
     * Boot method to set submitted_at automatically.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($kycDocument) {
            if (! $kycDocument->submitted_at) {
                $kycDocument->submitted_at = now();
            }
        });
    }
}
