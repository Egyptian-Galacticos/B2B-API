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
        $productParam = $request->route()->parameter('product');

        if (is_numeric($productParam)) {
            $product = Product::find($productParam);
        } else {
            $product = Product::where('slug', $productParam)->first();
        }

        if (! $product) {
            return $this->apiResponseErrors(
                'Product not found',
                ['product' => 'The specified product does not exist'],
                404
            );
        }

        $user = $request->user();

        if (! $user) {
            return $this->apiResponseErrors(
                'Unauthorized',
                ['user' => 'You must be logged in to perform this action'],
                401
            );
        }

        if ($product->seller_id !== $user->id) {
            return $this->apiResponseErrors(
                'Forbidden',
                ['product' => 'You do not have permission to access this product'],
                403
            );
        }

        $request->merge(['product' => $product]);

        return $next($request);
    }
}
