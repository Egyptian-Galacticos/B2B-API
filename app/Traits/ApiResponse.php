<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    public function apiResponse($data = null, string $message = '', int $status = 200, ?array $meta = null): JsonResponse
    {
        $response = [
            'success' => $status < 400,
            'message' => $message,
            'data'    => $data,
        ];

        if ($meta) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $status);
    }

    public function apiResponseErrors($message, $errors = [], $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    /**
     * Generate pagination metadata from a paginated query result
     *
     * @param mixed $paginatedQuery Laravel paginated query result
     * @param array $options Additional options for pagination metadata
     * @return array Pagination metadata
     */
    public function getPaginationMeta($paginatedQuery, array $options = []): array
    {
        $meta = [
            'limit'      => $paginatedQuery->perPage(),
            'total'      => $paginatedQuery->total(),
            'totalPages' => $paginatedQuery->lastPage(),
        ];

        if (! isset($options['includePage']) || $options['includePage']) {
            $meta['page'] = $paginatedQuery->currentPage();
        }

        if (isset($options['includeHasMore']) && $options['includeHasMore']) {
            $meta['has_more_pages'] = $paginatedQuery->hasMorePages();
        }

        return $meta;
    }
}
