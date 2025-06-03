<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     *
     * @response ProductResource[]
     *
     * @unauthenticated
     */
    public function index()
    {
        $products = Product::with(['seller', 'category'])
            ->where('is_active', true)
            ->paginate(10);

        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @unauthenticated
     */
    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        $product = Product::create($validated);

        return new ProductResource($product);
    }

    /**
     * Display the specified resource.
     *
     * @unauthenticated
     */
    public function show(string $id)
    {
        $product = Product::with(['seller', 'category'])
            ->findOrFail($id);

        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     *
     * @unauthenticated
     */
    public function update(UpdateProductRequest $request, string $id)
    {
        $product = Product::findOrFail($id);
        $validated = $request->validated();
        $product->update($validated);

        return new ProductResource($product);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @unauthenticated
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

            return response()->json(['message' => 'Product deleted successfully.'], 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Product not found.'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
