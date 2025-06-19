<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isDocument = str_starts_with($this->mime_type, 'application/');
        $data = [
            'id'        => $this->id,
            'name'      => $this->name,
            'file_name' => $this->file_name,
            'url'       => $this->getUrl(),
            'size'      => $this->size,
            'mime_type' => $this->mime_type,
        ];

        if (! $isDocument) {
            $data['thumbnail_url'] = $this->getUrl('thumb');
        }

        return $data;

    }
}
