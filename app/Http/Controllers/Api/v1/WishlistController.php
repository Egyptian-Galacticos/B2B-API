<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wishlist\AddToWishlistRequest;
use App\Http\Requests\Wishlist\RemoveFromWishlistRequest;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $user = auth()->user();
        $perPage = (int) $request->get('per_page', 15);

        // Get wishlist products with pagination and proper relationships
        $products = $user->wishlist()
            ->with([
                'seller.company',
                'category',
                'media',
                'tiers',
            ])
            ->where('is_active', true)
            ->orderByDesc('wishlist.created_at') // Order by when added to wishlist
            ->paginate($perPage);

        return $this->apiResponse(
            data: ProductResource::collection($products),
            message: 'Wishlist retrieved successfully.',
            status: 200,
            meta: [
                'total'          => $products->total(),
                'per_page'       => $products->perPage(),
                'current_page'   => $products->currentPage(),
                'last_page'      => $products->lastPage(),
                'has_more_pages' => $products->hasMorePages(),
            ]
        );
    }

    /**
     * Add a product to the wishlist.
     *
     * @authenticated
     */
    public function store(AddToWishlistRequest $request): JsonResponse
    {
        $productId = $request->validated()['product_id'];
        $user = auth()->user();

        // Check if product exists and is active
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

        // Prevent users from adding their own products
        if ($product->seller_id === $user->id) {
            return $this->apiResponseErrors(
                message: 'You cannot add your own product to wishlist.',
                errors: ['product_id' => ['You cannot add your own product to wishlist.']],
                status: 422
            );
        }

        // Check if product is already in wishlist
        if ($user->wishlist()->where('product_id', $productId)->exists()) {
            return $this->apiResponseErrors(
                message: 'Product is already in your wishlist.',
                errors: ['product_id' => ['Product is already in your wishlist.']],
                status: 409
            );
        }

        try {
            // Add product to wishlist using pivot table
            $user->wishlist()->attach($productId);

            return $this->apiResponse(
                data: new ProductResource($product),
                message: 'Product added to wishlist successfully.',
                status: 201
            );

        } catch (\Exception $e) {
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
    public function destroy(RemoveFromWishlistRequest $request): JsonResponse
    {
        $productId = $request->validated()['product_id'];
        $user = auth()->user();

        // Check if product is in wishlist
        if (! $user->wishlist()->where('product_id', $productId)->exists()) {
            return $this->apiResponseErrors(
                message: 'Product not found in your wishlist.',
                errors: ['product_id' => ['Product not found in your wishlist.']],
                status: 404
            );
        }

        try {
            // Remove product from wishlist
            $user->wishlist()->detach($productId);

            return $this->apiResponse(
                message: 'Product removed from wishlist successfully.',
                status: 200
            );

        } catch (\Exception $e) {
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
        $user = auth()->user();
        $productId = $request->validated()['product_id'];

        $inWishlist = $user->wishlist()->where('product_id', $productId)->exists();

        return $this->apiResponse(
            data: ['in_wishlist' => $inWishlist],
            message: 'Wishlist status checked successfully.',
            status: 200
        );
    }

    /**
     * Clear the entire wishlist.
     *
     * @authenticated
     */
    public function clear(): JsonResponse
    {
        $user = auth()->user();

        try {
            $removedCount = $user->wishlist()->count();
            $user->wishlist()->detach();

            return $this->apiResponse(
                data: ['removed_count' => $removedCount],
                message: 'Wishlist cleared successfully.',
                status: 200
            );

        } catch (\Exception $e) {
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
        $user = auth()->user();

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
    }
}
