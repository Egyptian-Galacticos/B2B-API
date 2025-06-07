<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;
    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'phone_number',
        'is_email_verified',
        'email_verified_at',
        'status',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_email_verified' => 'boolean',
            'status'            => 'string',
            'last_login_at'     => 'datetime',
        ];
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if user is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if email is verified.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->is_email_verified && $this->email_verified_at !== null;
    }

    public function emailVerificationTokens()
    {
        return $this->morphMany(EmailVerificationToken::class, 'verifiable');
    }

    /**
     * Get all audit logs for this user.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Get all KYC documents for this user.
     */
    public function kycDocuments(): HasMany
    {
        return $this->hasMany(KycDocument::class);
    }

    /**
     * Get pending KYC documents.
     */
    public function pendingKycDocuments(): HasMany
    {
        return $this->kycDocuments()->where('status', 'pending');
    }

    /**
     * Get approved KYC documents.
     */
    public function approvedKycDocuments(): HasMany
    {
        return $this->kycDocuments()->where('status', 'approved');
    }

    /**
     * Get all notifications for this user.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get unread notifications.
     */
    public function unreadNotifications(): HasMany
    {
        return $this->notifications()->where('is_read', false);
    }

    /**
     * Get KYC documents reviewed by this user (if they're a reviewer).
     */
    public function reviewedKycDocuments(): HasMany
    {
        return $this->hasMany(KycDocument::class, 'reviewer_id');
    }

    /**
     * Scope to get active users only.
     */
    // public function scopeActive($query)
    // {
    //     return $query->where('status', 'active');
    // }

    /**
     * Scope to get verified users only.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_email_verified', true);
    }

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomResetPasswordNotification($token, $this->email));
    }

    public function refreshToken(): HasOne
    {
        return $this->hasOne(RefreshToken::class);
    }

    public function company(): HasOne
    {
        return $this->hasOne(Company::class);
    }
}
