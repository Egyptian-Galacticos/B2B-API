<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\MediaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'description'    => $this->description,
            'status'         => $this->status,
            'level'          => $this->level,
            'path'           => $this->path,
            'icon'           => $this->icon,
            'parent_id'      => $this->parent_id,
            'products_count' => $this->products_count ?? 0,
            'children_count' => $this->children_count ?? 0,
            'created_at'     => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'     => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at'     => $this->deleted_at?->format('Y-m-d H:i:s'),

            'parent' => $this->whenLoaded('parent', function () {
                return [
                    'id'    => $this->parent->id,
                    'name'  => $this->parent->name,
                    'slug'  => $this->parent->slug,
                    'level' => $this->parent->level,
                ];
            }),

            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id'    => $this->creator->id,
                    'name'  => $this->creator->first_name.' '.$this->creator->last_name,
                    'email' => $this->creator->email,
                ];
            }),

            'updater' => $this->whenLoaded('updater', function () {
                return [
                    'id'    => $this->updater->id,
                    'name'  => $this->updater->first_name.' '.$this->updater->last_name,
                    'email' => $this->updater->email,
                ];
            }),

            'media' => MediaResource::collection($this->whenLoaded('media')),

            'full_path_names' => $this->getFullPathNames(),
            'can_be_deleted'  => $this->canBeDeleted(),
            'is_root'         => $this->isRoot(),
            'has_children'    => $this->hasChildren(),
            'is_active'       => $this->isActive(),
            'is_trashed'      => $this->trashed(),
        ];
    }
}
