<?php

namespace App\Http\Middleware;

use App\Models\Product;
use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckProductOwnership
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the product parameter value from the route (not the resolved model)
        $productParam = $request->route()->parameter('product');

        // Try to find the product by slug or ID
        if (is_numeric($productParam)) {
            $product = Product::find($productParam);
        } else {
            $product = Product::where('slug', $productParam)->first();
        }

        // Check if the product exists
        if (! $product) {
            return $this->apiResponseErrors(
                'Product not found',
                ['product' => 'The specified product does not exist'],
                404
            );
        }

        // Get the authenticated user
        $user = $request->user();

        // Check if user is authenticated
        if (! $user) {
            return $this->apiResponseErrors(
                'Unauthorized',
                ['user' => 'You must be logged in to perform this action'],
                401
            );
        }

        // Check if the user is the seller (owner) of the product
        if ($product->seller_id !== $user->id) {
            return $this->apiResponseErrors(
                'Forbidden',
                ['product' => 'You do not have permission to access this product'],
                403
            );
        }

        // Add the product to the request for easy access in the controller
        $request->merge(['product' => $product]);

        return $next($request);
    }
}
