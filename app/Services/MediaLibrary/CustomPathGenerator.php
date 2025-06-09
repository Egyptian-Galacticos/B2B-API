<?php

namespace App\Services\MediaLibrary;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class CustomPathGenerator implements PathGenerator
{
    /**
     * Get the path for the given media, relative to the root storage path.
     */
    public function getPath(Media $media): string
    {
        return $this->getBasePath($media).'/';
    }

    /**
     * Get the path for conversions of the given media, relative to the root storage path.
     */
    public function getPathForConversions(Media $media): string
    {
        return $this->getBasePath($media).'/conversions/';
    }

    /**
     * Get the path for responsive images of the given media, relative to the root storage path.
     */
    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getBasePath($media).'/responsive-images/';
    }

    /**
     * Generate a consistent base path for the media file.
     * Uses a combination of model info and media ID for uniqueness.
     */
    protected function getBasePath(Media $media): string
    {
        // Create a consistent hash using media ID and creation date (won't change)
        $hash = md5($media->id.$media->created_at->timestamp);

        // Get model information for organization
        $modelType = class_basename($media->model_type);
        $collectionName = $media->collection_name ?: 'default';

        // Create organized path: model/collection/hash
        return strtolower($modelType).'/'.$collectionName.'/'.$hash;
    }
}
