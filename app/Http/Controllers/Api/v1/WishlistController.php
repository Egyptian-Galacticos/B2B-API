<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wishlist\AddToWishlistRequest;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    use ApiResponse;

    /**
     * Display the wishlist products of the authenticated user.
     *
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $perPage = (int) $request->get('per_page', 15);

            $products = $user->wishlist()
                ->with([
                    'seller.company',
                    'category',
                    'media',
                    'tiers',
                ])
                ->where('is_active', true)
                ->orderByDesc('wishlist.created_at')
                ->paginate($perPage);

            return $this->apiResponse(
                data: ProductResource::collection($products),
                message: 'Wishlist retrieved successfully.',
                status: 200,
                meta: $this->getPaginationMeta($products)
            );

        } catch (Exception $e) {
            return $this->apiResponseErrors(
                message: 'Failed to retrieve wishlist.',
                errors: ['error' => ['An unexpected error occurred.']],
                status: 500
            );
        }
    }

    /**
     * Add a product to the wishlist.
     *
     * @authenticated
     */
    public function store(AddToWishlistRequest $request): JsonResponse
    {
        try {
            $productId = $request->validated()['product_id'];
            $user = Auth::user();

            $product = Product::with(['seller.company', 'category', 'media'])
                ->where('id', $productId)
                ->where('is_active', true)
                ->first();

            if (! $product) {
                return $this->apiResponseErrors(
                    message: 'Product not found or is inactive.',
                    errors: ['product_id' => ['The selected product is invalid or inactive.']],
                    status: 404
                );
            }

            if ($product->seller_id === $user->id) {
                return $this->apiResponseErrors(
                    message: 'You cannot add your own product to wishlist.',
                    errors: ['product_id' => ['You cannot add your own product to wishlist.']],
                    status: 422
                );
            }

            if ($user->wishlist()->where('product_id', $productId)->exists()) {
                return $this->apiResponseErrors(
                    message: 'Product is already in your wishlist.',
                    errors: ['product_id' => ['Product is already in your wishlist.']],
                    status: 409
                );
            }

            $user->wishlist()->attach($productId);

            return $this->apiResponse(
                data: new ProductResource($product),
                message: 'Product added to wishlist successfully.',
                status: 201
            );

        } catch (Exception $e) {
            return $this->apiResponseErrors(
                message: 'Failed to add product to wishlist.',
                errors: ['error' => ['An unexpected error occurred.']],
                status: 500
            );
        }
    }

    /**
     * Remove a product from the wishlist.
     *
     * @authenticated
     */
    public function destroy(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $productId = $request->query('product_id');

            if (! $productId) {
                return $this->apiResponseErrors(
                    message: 'Product ID is required.',
                    errors: ['product_id' => ['The product_id parameter is required.']],
                    status: 422
                );
            }

            // Check if the product exists
            $product = Product::find($productId);
            if (! $product) {
                return $this->apiResponseErrors(
                    message: 'Product not found.',
                    errors: ['product_id' => ['The selected product does not exist.']],
                    status: 404
                );
            }

            if (! $user->wishlist()->where('product_id', $productId)->exists()) {
                return $this->apiResponseErrors(
                    message: 'Product not found in your wishlist.',
                    errors: ['product_id' => ['Product not found in your wishlist.']],
                    status: 404
                );
            }

            $user->wishlist()->detach($productId);

            return $this->apiResponse(
                message: 'Product removed from wishlist successfully.',
                status: 200
            );

        } catch (Exception $e) {
            return $this->apiResponseErrors(
                message: 'Failed to remove product from wishlist.',
                errors: ['error' => ['An unexpected error occurred.']],
                status: 500
            );
        }
    }

    /**
     * Check if a product is in the user's wishlist.
     *
     * @authenticated
     */
    public function check(AddToWishlistRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $productId = $request->validated()['product_id'];

            $inWishlist = $user->wishlist()->where('product_id', $productId)->exists();

            return $this->apiResponse(
                data: ['in_wishlist' => $inWishlist],
                message: 'Wishlist status checked successfully.',
                status: 200
            );

        } catch (Exception $e) {
            return $this->apiResponseErrors(
                message: 'Failed to check wishlist status.',
                errors: ['error' => ['An unexpected error occurred.']],
                status: 500
            );
        }
    }

    /**
     * Clear the entire wishlist.
     *
     * @authenticated
     */
    public function clear(): JsonResponse
    {
        try {
            $user = Auth::user();
            $removedCount = $user->wishlist()->count();
            $user->wishlist()->detach();

            return $this->apiResponse(
                data: ['removed_count' => $removedCount],
                message: 'Wishlist cleared successfully.',
                status: 200
            );

        } catch (Exception $e) {
            return $this->apiResponseErrors(
                message: 'Failed to clear wishlist.',
                errors: ['error' => ['An unexpected error occurred.']],
                status: 500
            );
        }
    }

    /**
     * Get wishlist summary/statistics.
     *
     * @authenticated
     */
    public function summary(): JsonResponse
    {
        try {
            $user = Auth::user();
            $wishlistProducts = $user->wishlist()->with('category')->get();

            $totalValue = $wishlistProducts->sum('price');
            $currencies = $wishlistProducts->pluck('currency')->unique()->values();
            $categories = $wishlistProducts->pluck('category.name')->unique()->values();

            return $this->apiResponse(
                data: [
                    'total_items' => $wishlistProducts->count(),
                    'total_value' => $totalValue,
                    'currencies'  => $currencies,
                    'categories'  => $categories,
                ],
                message: 'Wishlist summary retrieved successfully.',
                status: 200
            );

        } catch (Exception $e) {
            return $this->apiResponseErrors(
                message: 'Failed to retrieve wishlist summary.',
                errors: ['error' => ['An unexpected error occurred.']],
                status: 500
            );
        }
    }
}
