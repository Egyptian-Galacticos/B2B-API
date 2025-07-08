<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Services\QueryHandler;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ProductService
{
    /**
     * Get all products across all sellers with filtering and pagination.
     */
    public function getAllProducts(array $filters, Request $request)
    {
        $queryHandler = new QueryHandler($request);
        $perPage = (int) $request->get('size', 10);

        $query = $queryHandler
            ->setBaseQuery(
                Product::query()
                    ->with(['seller.company', 'category', 'tags', 'media'])
            )
            ->setAllowedSorts([
                'id',
                'name',
                'brand',
                'currency',
                'weight',
                'is_active',
                'is_approved',
                'is_featured',
                'created_at',
                'updated_at',
                'seller.name',
                'seller.company.name',
                'category.name',
            ])
            ->setAllowedFilters([
                'name',
                'brand',
                'currency',
                'is_active',
                'is_approved',
                'is_featured',
                'seller_id',
                'category_id',
            ])
            ->apply();

        $result = $query->paginate($perPage)->withQueryString();

        return $result;
    }

    /**
     * Get product by ID with full details.
     */
    public function getProductById(int $id): array
    {
        try {
            $product = Product::with(['seller.company', 'category', 'tiers', 'media', 'tags'])
                ->findOrFail($id);

            return [
                'success' => true,
                'product' => $product,
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'Product not found.',
                'status'  => 404,
            ];
        }
    }

    /**
     * Update product status (approve/reject/feature/active).
     */
    public function updateProductStatus(int $id, array $data): array
    {
        try {
            $product = Product::findOrFail($id);

            if ($data['is_approved'] === true && ! $product->category->isPathApproved()) {
                throw new DomainException('Cannot approve product as its category path is not approved.');
            }

            $product->update($data);

            return [
                'success' => true,
                'product' => $product->fresh(),
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'Product not found.',
                'status'  => 404,
            ];
        } catch (DomainException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => ['category_path' => [$e->getMessage()]],
                'status'  => 400,
            ];
        }

    }

    /**
     * Handle bulk actions on products.
     */
    public function bulkActionProducts(array $productIds, string $action): array
    {
        $updateData = [];
        $actionMessage = '';

        switch ($action) {
            case 'approve':
                $updateData = ['is_approved' => true];
                $actionMessage = 'approved';
                break;
            case 'reject':
                $updateData = ['is_approved' => false];
                $actionMessage = 'rejected';
                break;
            case 'activate':
                $updateData = ['is_active' => true];
                $actionMessage = 'activated';
                break;
            case 'deactivate':
                $updateData = ['is_active' => false];
                $actionMessage = 'deactivated';
                break;
            case 'feature':
                $updateData = ['is_featured' => true];
                $actionMessage = 'featured';
                break;
            case 'unfeature':
                $updateData = ['is_featured' => false];
                $actionMessage = 'unfeatured';
                break;
            case 'delete':
                $deletedCount = Product::whereIn('id', $productIds)->delete();

                return [
                    'success' => true,
                    'message' => "{$deletedCount} products have been deleted successfully.",
                ];
            case 'restore':
                $restoredCount = Product::onlyTrashed()->whereIn('id', $productIds)->restore();

                return [
                    'success' => true,
                    'message' => "{$restoredCount} products have been restored successfully.",
                ];
            default:
                return [
                    'success' => false,
                    'message' => 'Invalid bulk action specified.',
                ];
        }

        $affectedCount = Product::whereIn('id', $productIds)->update($updateData);

        return [
            'success' => true,
            'message' => "{$affectedCount} products have been {$actionMessage} successfully.",
        ];
    }

    /**
     * Delete a specific product (admin only).
     */
    public function deleteProduct(int $id): array
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

            return [
                'success' => true,
                'message' => 'Product deleted successfully.',
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'Product not found.',
                'status'  => 404,
            ];
        }
    }

    /**
     * Restore a soft-deleted product.
     */
    public function restoreProduct(int $id): array
    {
        try {
            $product = Product::onlyTrashed()->findOrFail($id);
            $product->restore();
            if (! $product) {
                return [
                    'success' => false,
                    'message' => 'Deleted product not found.',
                    'status'  => 404,

                ];
            }

            return [
                'success' => true,
                'message' => 'Product restored successfully.',
                'product' => $product->fresh(),
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'Deleted product not found.',
                'status'  => 404,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all trashed products.
     */
    public function getTrashedProducts(Request $request)
    {
        $perPage = (int) $request->get('size', 10);

        $query = Product::onlyTrashed()
            ->with(['seller.company', 'category', 'tags', 'media']);

        return $query->paginate($perPage)->withQueryString();
    }
}
