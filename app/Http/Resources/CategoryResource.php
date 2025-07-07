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
            'creator'     => $this->whenLoaded('creator', fn () => [
                'id'         => $this->creator->id,
                'first_name' => $this->creator->first_name,
                'last_name'  => $this->creator->last_name,
                'email'      => $this->creator->email,
            ]),
            'updater' => $this->whenLoaded('updater', fn () => [
                'id'         => $this->updater->id,
                'first_name' => $this->updater->first_name,
                'last_name'  => $this->updater->last_name,
                'email'      => $this->updater->email,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
