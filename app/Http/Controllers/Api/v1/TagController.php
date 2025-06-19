<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Spatie\Tags\Tag;

class TagController extends Controller
{
    use ApiResponse;

    /**
     * @unauthenticated
     */
    public function index(): JsonResponse
    {
        try {
            $tags = Cache::remember('tags.all', now()->addMinute(30), function () {
                return Tag::select('name')->get();
            });

            return $this->apiResponse(
                data: TagResource::collection($tags),
                message: 'Tags retrieved successfully.',
                status: 200
            );
        } catch (\Exception $e) {
            return $this->apiResponseErrors(
                message: 'Failed to retrieve tags.',
                errors: [$e->getMessage()],
                status: 500
            );
        }

    }

    /**
     * @unauthenticated
     */
    public function clearCache(): JsonResponse
    {
        Cache::forget('tags.all');

        return $this->apiResponse(
            data: [],
            message: 'tags cashed cleared successfully.'
        );
    }
}
