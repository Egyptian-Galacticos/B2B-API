<?php

namespace App\Traits;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait HasCreatorAndUpdater
{
    /**
     * Boot the trait and set up model event listeners.
     */
    public static function bootHasCreatorAndUpdater(): void
    {
        static::creating(function (Model $model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
                $model->updated_by = Auth::id();

                if ($model instanceof Category) {
                    $user = Auth::user();
                    if (! $user->isAdmin()) {
                        $model->status = 'pending';
                    }
                }
            }
        });

        static::updating(function (Model $model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }

    /**
     * Get the user who created this model.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this model.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if the model was created by the given user.
     */
    public function wasCreatedBy(User $user): bool
    {
        return $this->created_by === $user->id;
    }

    /**
     * Check if the model was last updated by the given user.
     */
    public function wasUpdatedBy(User $user): bool
    {
        return $this->updated_by === $user->id;
    }

    /**
     * Get the creator's name.
     */
    public function getCreatorNameAttribute(): ?string
    {
        return $this->creator ? $this->creator->first_name.' '.$this->creator->last_name : null;
    }

    /**
     * Get the updater's name.
     */
    public function getUpdaterNameAttribute(): ?string
    {
        return $this->updater ? $this->updater->first_name.' '.$this->updater->last_name : null;
    }
}
