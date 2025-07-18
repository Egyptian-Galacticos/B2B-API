<?php

namespace App\Models;

use App\Traits\HasCreatorAndUpdater;
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
    use HasCreatorAndUpdater, HasFactory, HasSlug, InteractsWithMedia, SoftDeletes;

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
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'level' => 'integer',
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

    public function recursiveChildren(): HasMany
    {
        return $this->children()->with('recursiveChildren');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
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

    public function isPathApproved(): bool
    {
        $current = $this;
        while ($current) {
            if ($current->status !== 'active') {
                return false;
            }
            $current = $current->parent;
        }

        return true;
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

    public function determineStatusByUserRole($user): string
    {
        return $this->userIsAdmin($user) ? 'active' : 'pending';
    }

    /**
     * Check if user is admin.
     */
    public function userIsAdmin($user): bool
    {
        if (is_numeric($user)) {
            $user = User::find($user);
        }
        if (! $user || ! ($user instanceof User)) {
            return false;
        }
        if (! $user->relationLoaded('roles')) {
            $user->load('roles');
        }

        return $user->hasRole('admin');
    }

    /**
     * Handle file uploads for category.
     */
    public function handleFileUploads($request): void
    {
        if ($request->hasFile('image_file')) {
            $this->clearMediaCollection('images');
            $this->addMediaFromRequest('image_file')
                ->toMediaCollection('images');
        }

        if ($request->hasFile('icon_file')) {
            $this->clearMediaCollection('icons');
            $this->addMediaFromRequest('icon_file')
                ->toMediaCollection('icons');
        }
    }

    /**
     * Handle file removals for category.
     */
    public function handleFileRemovals($request): void
    {
        if ($request->boolean('remove_image')) {
            $this->clearMediaCollection('images');
        }

        if ($request->boolean('remove_icon_file')) {
            $this->clearMediaCollection('icons');
        }
    }

    /**
     * Check if setting parent would create circular reference.
     */
    public function wouldCreateCircularReference(Category $parent): bool
    {
        $currentParent = $parent;

        while ($currentParent) {
            if ($currentParent->id === $this->id) {
                return true;
            }
            $currentParent = $currentParent->parent;
        }

        return false;
    }

    /**
     * Update paths for all children when parent hierarchy changes.
     */
    public function updateChildrenPaths(): void
    {
        $children = static::where('parent_id', $this->id)->get();

        foreach ($children as $child) {
            $newPath = $this->path ? $this->path.'/'.$this->id : (string) $this->id;
            $child->update([
                'level' => $this->level + 1,
                'path'  => $newPath,
            ]);

            $child->updateChildrenPaths();
        }
    }

    /**
     * Calculate level and path based on parent.
     */
    public function calculateHierarchyData(?int $parentId = null): array
    {
        $level = 0;
        $path = null;

        if ($parentId) {
            $parent = static::findOrFail($parentId);
            $level = $parent->level + 1;
            $path = $parent->path ? $parent->path.'/'.$parent->id : (string) $parent->id;
        }

        return compact('level', 'path');
    }

    /**
     * Check if category can be deleted (no children or products).
     */
    public function canBeDeleted(): bool
    {
        return ! $this->hasChildren() && ! $this->products()->exists();
    }

    /**
     * Get all ancestors of the category.
     */
    public function getAncestors()
    {
        $ancestors = collect();
        $current = $this->parent;

        while ($current) {
            $ancestors->prepend($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Get all descendants of the category.
     */
    public function getDescendants()
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getDescendants());
        }

        return $descendants;
    }

    /**
     * Get the full path names as a string.
     */
    public function getFullPathNames(string $separator = ' > '): string
    {
        if (empty($this->path)) {
            return $this->name;
        }

        $ancestorIds = explode('/', $this->path);

        $ancestors = Category::whereIn('id', $ancestorIds)
            ->orderByRaw('FIELD(id, '.implode(',', $ancestorIds).')')
            ->pluck('name')
            ->toArray();

        $ancestors[] = $this->name;

        return implode($separator, $ancestors);
    }
}
