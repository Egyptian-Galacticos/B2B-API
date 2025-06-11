<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'description'   => $this->description,
            'slug'          => $this->slug,
            'parent_id'     => $this->parent_id,
            'level'         => $this->level,
            'path'          => $this->path,
            'status'        => $this->status,
            'icon'          => $this->icon,
            'image_url'     => $this->getImageUrl(),
            'icon_url'      => $this->getIconUrl(),
            'thumbnail_url' => $this->getThumbnailUrl(),
            'seo_metadata'  => $this->seo_metadata,
            'parent'        => $this->whenLoaded('parent', fn () => new CategoryResource($this->parent)),
            'children'      => CategoryResource::collection($this->whenLoaded('recursiveChildren')),
            'creator'       => $this->whenLoaded('creator', fn () => [
                'id'        => $this->creator->id,
                'full_name' => $this->creator->full_name,
            ]),
            'updater' => $this->whenLoaded('updater', fn () => [
                'id'        => $this->updater->id,
                'full_name' => $this->updater->full_name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
