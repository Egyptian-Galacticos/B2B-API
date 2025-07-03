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
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'slug'        => $this->slug,
            'parent_id'   => $this->parent_id,
            'level'       => $this->level,
            'path'        => $this->path,
            'status'      => $this->status,
            'icon'        => $this->icon,
            'image'       => ($firstMedia = $this->getFirstMedia('images')) ? new MediaResource($firstMedia) : null,
            'parent'      => $this->whenLoaded('parent', fn () => new CategoryResource($this->parent)),
            'children'    => CategoryResource::collection($this->whenLoaded('children')),
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
