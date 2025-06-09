<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'path',
        'level',
        'is_active',
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];

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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeRootCategories($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeWithActiveProducts($query)
    {
        return $query->whereHas('products', function ($q) {
            $q->where('is_active', true);
        });
    }

    public function scopeWithInactiveProducts($query)
    {
        return $query->whereHas('products', function ($q) {
            $q->where('is_active', false);
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
        return $this->is_active == true;
    }

    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    public function isChild(): bool
    {
        return $this->children()->count() === 0;
    }
}
