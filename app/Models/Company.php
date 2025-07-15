<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Company extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'tax_id',
        'company_phone',
        'commercial_registration',
        'address',
        'logo',
        'website',
        'description',
        'is_email_verified',
        'user_id',
        'city',
        'country',
        'type',
        'is_verified',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'address'           => 'array',
            'is_email_verified' => 'boolean',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
            'user_id'           => 'integer',
        ];
    }

    /**
     * Get the user that owns the company.
     */
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Get the company's full address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get formatted website URL.
     */
    public function getFormattedWebsiteAttribute(): ?string
    {
        if (! $this->website) {
            return null;
        }
        if (! str_starts_with($this->website, 'http://') && ! str_starts_with($this->website, 'https://')) {
            return 'https://'.$this->website;
        }

        return $this->website;
    }

    /**
     * Check if company is verified.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->is_email_verified;
    }

    public function emailVerificationTokens()
    {
        return $this->morphMany(EmailVerificationToken::class, 'verifiable');
    }

    public function setUnverifiedEmail()
    {
        $this->is_email_verified = false;
        $this->save();
    }

    /**
     * Get company type display name.
     */
    public function getTypeDisplayAttribute(): string
    {
        return match ($this->type) {
            'corporation'         => 'Corporation',
            'llc'                 => 'Limited Liability Company',
            'partnership'         => 'Partnership',
            'sole_proprietorship' => 'Sole Proprietorship',
            'other'               => 'Other',
            default               => 'Unknown',
        };
    }

    /**
     * Scope to get verified companies only.
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to get companies by country.
     */
    public function scopeByCountry(Builder $query, string $country): Builder
    {
        return $query->where('country', $country);
    }

    /**
     * Scope to get companies by type.
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to search companies by name.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('tax_id', 'like', "%{$search}%")
                ->orWhere('commercial_registration', 'like', "%{$search}%");
        });
    }

    /**
     * Mark company as verified.
     */
    public function markAsVerified(): bool
    {
        return $this->update(['is_verified' => true]);
    }

    /**
     * Mark company as unverified.
     */
    public function markAsUnverified(): bool
    {
        return $this->update(['is_verified' => false]);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml']);

        $this->addMediaCollection('tax_id_images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

        $this->addMediaCollection('commercial_registration_images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    /**
     * Register media conversions for the company.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        // Logo conversions
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->performOnCollections('logo');

        $this->addMediaConversion('small')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->performOnCollections('logo');

        // Tax ID images conversions
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(300)
            ->sharpen(10)
            ->performOnCollections('tax_id_images');

        $this->addMediaConversion('small')
            ->width(200)
            ->height(150)
            ->sharpen(10)
            ->performOnCollections('tax_id_images');

        // Commercial registration images conversions
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(300)
            ->sharpen(10)
            ->performOnCollections('commercial_registration_images');

        $this->addMediaConversion('small')
            ->width(200)
            ->height(150)
            ->sharpen(10)
            ->performOnCollections('commercial_registration_images');
    }

    /**
     * Should queue media conversions.
     */
    public function shouldQueueMediaConversion(?Media $media = null): bool
    {
        return true;
    }
}
