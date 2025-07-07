<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements HasMedia, JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, InteractsWithMedia, Notifiable, SoftDeletes;
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
     * Register media collections for the user.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    /**
     * Register media conversions for the user.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->performOnCollections('profile_image');

        $this->addMediaConversion('small')
            ->width(50)
            ->height(50)
            ->sharpen(10)
            ->performOnCollections('profile_image');
    }

    /**
     * Should queue media conversions.
     */
    public function shouldQueueMediaConversion(?Media $media = null): bool
    {
        return true;
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
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isSeller(): bool
    {
        return $this->hasRole('seller');
    }

    public function isBuyer(): bool
    {
        return $this->hasRole('buyer');
    }

    /**
     * Check if user can act in a specific role for a given entity context
     */
    public function canActInRole(string $role, $entity = null): bool
    {
        if (! $this->hasRole($role)) {
            return false;
        }

        if (! $entity) {
            return true;
        }

        $roleField = $role.'_id';

        if (isset($entity->$roleField) && $this->id === $entity->$roleField) {
            return true;
        }

        if (isset($entity->rfq) && isset($entity->rfq->$roleField) && $this->id === $entity->rfq->$roleField) {
            return true;
        }

        return false;
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
     * Get the user's wishlist products (many-to-many pivot table).
     */
    public function wishlist(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'wishlist')
            ->withTimestamps();
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
     * Get products owned by this user (when they are a seller).
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    /**
     * Get conversations where this user is the seller.
     */
    public function sellerConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'seller_id');
    }

    /**
     * Get conversations where this user is the buyer.
     */
    public function buyerConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'buyer_id');
    }

    /**
     * Get all conversations for this user (as seller or buyer).
     */
    public function conversations()
    {
        return Conversation::where('seller_id', $this->id)
            ->orWhere('buyer_id', $this->id);
    }

    /**
     * Get messages sent by this user.
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Get unread messages for this user.
     */
    public function unreadMessages()
    {
        return Message::whereHas('conversation', function ($q) {
            $q->where('seller_id', $this->id)->orWhere('buyer_id', $this->id);
        })->where('sender_id', '!=', $this->id)->where('is_read', false);
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
        return $this->hasOne(Company::class)->withTrashed();
    }
}
