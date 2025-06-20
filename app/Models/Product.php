<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Tags\HasTags;

class Product extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;
    use HasSlug;
    use HasTags;
    use InteractsWithMedia;
    protected $fillable = [
        'brand',
        'model_number',
        'sku',
        'name',
        'slug',
        'description',
        'hs_code',
        'weight',
        'currency',
        'minimum_order_quantity',
        'lead_time_days',
        'origin',
        'specifications',
        'dimensions',
        'category_id',
        'sample_available',
        'sample_price',
        'is_active',
        'is_approved',
        'is_featured',
        'seller_id',
    ];
    protected $casts = [
        'specifications'   => 'array',
        'certifications'   => 'array',
        'dimensions'       => 'array',
        'is_active'        => 'boolean',
        'is_approved'      => 'boolean',
        'is_featured'      => 'boolean',
        'sample_available' => 'boolean',
        'sample_price'     => 'decimal:2',
    ];
    protected $with = ['media'];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name', 'model_number')
            ->saveSlugsTo('slug');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('main_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

        $this->addMediaCollection('product_images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

        $this->addMediaCollection('product_documents')
            ->acceptsMimeTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(368)
            ->height(232)
            ->sharpen(10)
            ->performOnCollections('main_image', 'product_images');
    }

    public function shouldQueueMediaConversion(?Media $media = null): bool
    {
        return true;
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($product) {
            $product->is_approved = $product->is_approved ?? false;
            $product->is_active = $product->is_active ?? true;
            $product->is_featured = $product->is_featured ?? false;
        });
        static::updating(function ($product) {
            $product->is_approved = $product->is_approved ?? false;
            $product->is_active = $product->is_active ?? true;
            $product->is_featured = $product->is_featured ?? false;
        });
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function contractItems(): HasMany
    {
        return $this->hasMany(ContractItem::class);
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(PriceTier::class);
    }

    public function wishlistedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wishlist')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeBySeller($query, $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeBySku($query, $sku)
    {
        return $query->where('sku', $sku);
    }

    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', '%'.$name.'%');
    }

    public function scopeByWeightRange($query, $minWeight, $maxWeight)
    {
        return $query->whereBetween('weight', [$minWeight, $maxWeight]);
    }

    public function isActive(): bool
    {
        return $this->is_active == true;
    }
}
