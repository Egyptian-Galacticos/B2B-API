<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Category extends Model implements HasMedia
{
    use HasFactory, HasSlug, InteractsWithMedia, SoftDeletes;

    /** @use HasFactory<CategoryFactory> */
    protected $fillable = [
        'name',
        'description',
        'slug',
        'parent_id',
        'path',
        'level',
        'status',
        'icon',
        'seo_metadata',
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'seo_metadata' => 'array',
        'level'        => 'integer',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
            ->singleFile();

        $this->addMediaCollection('icons')
            ->acceptsMimeTypes(['image/svg+xml', 'image/png', 'image/jpeg'])
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->performOnCollections('images');

        $this->addMediaConversion('medium')
            ->width(600)
            ->height(600)
            ->sharpen(10)
            ->performOnCollections('images');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeRootCategories($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeWithActiveProducts($query)
    {
        return $query->whereHas('products', function ($q) {
            $q->where('status', 'active');
        });
    }

    public function scopeWithInactiveProducts($query)
    {
        return $query->whereHas('products', function ($q) {
            $q->where('status', 'inactive');
        });
    }

    public function scopeWithProducts($query)
    {
        return $query->whereHas('products');
    }

    public function scopeWithoutProducts($query)
    {
        return $query->doesntHave('products');
    }

    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', '%'.$name.'%');
    }

    public function scopeByParentId($query, $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeByPath($query, $path)
    {
        return $query->where('path', 'like', '%'.$path.'%');
    }

    public function isActive(): bool
    {
        return $this->status == 'active';
    }

    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    public function isChild(): bool
    {
        return $this->children()->count() === 0;
    }

    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    public function getImageUrl(): ?string
    {
        return $this->getFirstMediaUrl('images');
    }

    public function getIconUrl(): ?string
    {
        return $this->getFirstMediaUrl('icons');
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->getFirstMediaUrl('images', 'thumb');
    }
}
