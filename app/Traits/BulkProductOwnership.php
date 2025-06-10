<?php

namespace App\Traits;

use App\Models\Product;

trait BulkProductOwnership
{
    /**
     * Verify ownership of products for bulk operations
     */
    protected function verifyBulkOwnership(array $productIds, int $userId): array
    {
        // Get products owned by the user
        $userProducts = Product::whereIn('id', $productIds)
            ->where('seller_id', $userId)
            ->pluck('id')
            ->toArray();

        // Find unauthorized products
        $unauthorizedIds = array_diff($productIds, $userProducts);

        return [
            'authorized'       => $userProducts,
            'unauthorized'     => $unauthorizedIds,
            'has_unauthorized' => ! empty($unauthorizedIds),
        ];
    }

    /**
     * Generate ownership error response
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function ownershipErrorResponse(string $action, array $unauthorizedIds, array $authorizedIds)
    {
        return $this->apiResponseErrors(
            "You do not have permission to {$action} some of the specified products.",
            [
                'unauthorized_ids' => $unauthorizedIds,
                'authorized_ids'   => $authorizedIds,
            ],
            403
        );
    }

    /**
     * Generate no products found error response
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function noProductsFoundResponse(array $requestedIds)
    {
        return $this->apiResponseErrors(
            'No products found that you own.',
            ['requested_ids' => $requestedIds],
            404
        );
    }
}
