<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\AdminProductFilterRequest;
use App\Http\Requests\Admin\Product\BulkProductActionRequest;
use App\Http\Requests\Admin\Product\UpdateProductStatusRequest;
use App\Http\Resources\Product\ProductDetailsResource;
use App\Http\Resources\Product\ProductResource;
use App\Services\Admin\ProductService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProductController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * Display all products across all sellers for admin.
     *
     * @authenticated
     */
    public function index(AdminProductFilterRequest $request): JsonResponse
    {
        $paginatedProducts = $this->productService->getAllProducts($request->validated(), $request);

        return $this->apiResponse(
            ProductResource::collection($paginatedProducts),
            'Products retrieved successfully.',
            200,
            $this->getPaginationMeta($paginatedProducts)
        );
    }

    /**
     * Display the specified product details.
     *
     * @authenticated
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->productService->getProductById($id);

        if (! $result['success']) {
            return $this->apiResponseErrors(
                $result['message'],
                [],
                $result['status']
            );
        }

        return $this->apiResponse(
            new ProductDetailsResource($result['product']),
            'Product retrieved successfully.',
            200
        );
    }

    /**
     * Update product status (approve/reject/feature).
     *
     * @authenticated
     */
    public function updateStatus(UpdateProductStatusRequest $request, int $id): JsonResponse
    {
        $result = $this->productService->updateProductStatus($id, $request->validated());

        if (! $result['success']) {
            return $this->apiResponseErrors(
                $result['message'],
                [],
                $result['status']
            );
        }

        return $this->apiResponse(
            new ProductDetailsResource($result['product']),
            'Product status updated successfully.',
            200
        );
    }

    /**
     * Handle bulk actions on products.
     *
     * @authenticated
     */
    public function bulkAction(BulkProductActionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $productIds = $validated['product_ids'];
        $action = $validated['action'];

        $result = $this->productService->bulkActionProducts($productIds, $action);

        if (! $result['success']) {
            return $this->apiResponseErrors(
                $result['message'],
                [],
                400
            );
        }

        return $this->apiResponse(
            null,
            $result['message'],
            200
        );
    }

    /**
     * Delete a specific product (admin only).
     *
     * @authenticated
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->productService->deleteProduct($id);

        if (! $result['success']) {
            return $this->apiResponseErrors(
                $result['message'],
                [],
                $result['status']
            );
        }

        return $this->apiResponse(
            null,
            'Product deleted successfully.',
            200
        );
    }

    /**
     * Get trashed products (admin only).
     *
     * @authenticated
     */
    public function trashed(Request $request): JsonResponse
    {
        $trashedProducts = $this->productService->getTrashedProducts($request);

        return $this->apiResponse(
            ProductResource::collection($trashedProducts->items()),
            'Trashed products retrieved successfully.',
            200,
            $this->getPaginationMeta($trashedProducts)
        );
    }

    /**
     * Restore a soft-deleted product (admin only).
     *
     * @authenticated
     */
    public function restore(int $id): JsonResponse
    {
        $result = $this->productService->restoreProduct($id);

        if (! $result['success']) {
            return $this->apiResponseErrors(
                $result['message'],
                [],
                $result['status']
            );
        }

        return $this->apiResponse(
            new ProductResource($result['product']),
            'Product restored successfully.',
            200
        );
    }
}
