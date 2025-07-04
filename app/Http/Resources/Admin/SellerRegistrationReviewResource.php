<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerRegistrationReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'action'       => $this['action'],
            'company_id'   => $this['company_id'],
            'company_name' => $this['company_name'],
            'user_id'      => $this['user_id'],
            'user_email'   => $this['user_email'],
            'reason'       => $this['reason'],
            'notes'        => $this['notes'],
            'reviewed_at'  => $this['reviewed_at'],
            'reviewed_by'  => $this['reviewed_by'],
        ];
    }
}
